<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ProvidersController extends Controller
{
    
    public function __construct(){
        $access = env("ACCESS_FILE");//conexion a access de cedis
        if(file_exists($access)){
        try{  $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
    }

    public function index(){//validacion de proveedores igual que en vizapp y en sucursales
        $mysql = [];//contenedor para proveedores mysql
        $factusol = [];//contenedor para proveedores en factusol
        $inex = [//contenedor de inexistentes
            "fs"=>[],//contenedor de inexistentes en factusol
            "ms"=>[]//contenedor de inexistentes en mysql
        ];

        $mysqlprov = DB::table('providers')->get();//muestra todos los proveedores de mysql
        foreach($mysqlprov as $provider){
            $promy = [//se solicita solo codigo y nombre
                "codigo"=>$provider->id,
                "nombre"=>$provider->fiscal_name
            ];
            $mysql[] = $promy;//se guarda en contenedor de mysql
            $idsms[]= $provider->id;//se obtienen los ids de proveedores existentes en mysql
        }

        $sqlaccess = "SELECT CODPRO, NOFPRO FROM F_PRO WHERE CODPRO NOT IN (".implode(",",$idsms).")" ;//query para saver que proveedores faltan en mysql
        $exec = $this->conn->prepare($sqlaccess);
        $exec -> execute();
        $fil=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($fil){//se comprueba que haya filas
            $colsTab = array_keys($fil[0]);
            foreach($fil as $provid){foreach($colsTab as $col){ $provid[$col] = utf8_encode($provid[$col]); }
            $pro = [//se solicita codigo y nombre
                "codigo"=>$provid['CODPRO'],
                "nombre"=>$provid['NOFPRO'],
            ];

            $inex["ms"][] =$pro;// se guarda en contenedor de faltantes de mysql
        }
        }else{$inex["ms"][]=null;}//en caso de no haber me devueve un nulo

        $sql = "SELECT CODPRO, NOFPRO FROM F_PRO";//muestra los proveedores de access
        $exec = $this->conn->prepare($sql);
        $exec -> execute();
        $filaspro=$exec->fetchall(\PDO::FETCH_ASSOC);
        $colsTab = array_keys($filaspro[0]);
        foreach($filaspro as $provi){foreach($colsTab as $col){ $provi[$col] = utf8_encode($provi[$col]); }
            $provide = [//se obtiene codigo y nombre
                "codigo"=>$provi['CODPRO'],
                "nombre"=>$provi['NOFPRO']
            ];
            $factusol[]=$provide;//se guarda en contenedro de mysql
            $idsfs[]=intval($provi['CODPRO']);//se obtienen los id de proveedores existentes en factusol
        }
        $mysq = DB::table('providers')->whereNotIn('id',$idsfs)->get();//queru para saber que proveedores no estan en factusol
        if(count($mysq) > 0){//se cuenta que haya mayor a 0 
        foreach($mysq as $notprovider){
            $notprov = [//en caso de haber mas de 0 se obtiene codigo y nombre
                "codigo"=>$notprovider->id,
                "nombre"=>$notprovider->fiscal_name
            ];
            $inex["fs"][] =$notprov;//se guardan en contenedor de faltantes en factusol
        }
        }else{$inex["fs"][] =null;}//en caso de no haber devuelve nulo

        $res = [//arreglo de respuesta
            "factusol" =>$factusol,
            "mysql"=>$mysql,
            "inexistente"=>$inex
        ];
        return response()->json($res,200);
    }

    public function refreshProvider(){//SE ELIMINAN Y SE INSERTAN EN MYSQL TAL CUAL ESTA EN FACTUSOL
        
        $created = [];//cpntenedor para guardar datos creados
        $fail = [];//contenedo para guardar datos fallidos
        DB::statement("SET SQL_SAFE_UPDATES = 0;");//se desactiva safe update
        DB::statement("SET FOREIGN_KEY_CHECKS = 0;");//se desactivan las llaves foraneas
        $delete = DB::table('providers')->truncate();//se vacia la tabla de proveedores
        DB::statement("SET SQL_SAFE_UPDATES = 1;");//se activan las llaves foraneas
        DB::statement("SET FOREIGN_KEY_CHECKS = 1;");//se activa safe update
        $sql = "SELECT CODPRO, NOFPRO, DOMPRO, POBPRO, CPOPRO, PROPRO FROM F_PRO";//query selector de proveedores
        $exec = $this->conn->prepare($sql);
        $exec -> execute();
        $filaspro=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($filaspro){//si hay filas creadas
            $colsTab = array_keys($filaspro[0]);//llaves
            foreach($filaspro as $provider){foreach($colsTab as $col){ $provider[$col] = utf8_encode($provider[$col]); }//se codifican bien las llaves
                $providers = [//arreglo proveedores para mysql
                    "id"=>$provider['CODPRO'],
                    "fiscal_name"=>$provider['NOFPRO'],
                    "address"=> json_encode([
                        "domicilio"=>$provider['DOMPRO'],
                        "poblacion"=>$provider['DOMPRO'],
                        "codigo_postal"=>$provider['DOMPRO'],
                        "delegacion"=>$provider['DOMPRO'],
                    ])
                    ];
                $insert = DB::table('providers')->insert($providers);//se inserta en la tabla de proveedores
                if($insert){//si es insertado
                $created[] = $providers;//se guardan en los creados
                }else{$fail[] = $provider;}// en caso que no se guarda en fallidos
            }
            $response = [//se crea la respuesta
                "created"=>$created,
                "failed"=>$fail
            ];
            return response()->json($response,201);
        }else{return response()->json("No se encontraron proveedores",404);}
    }


    public function replyprovider(Request $request){//replicador de proveedores
        $date = $request->date;//se recibe fecha
        $created = [];//contenedor de creads en mysql
        $update = [];//contenedor de actualizados en mysql
        $fail = [];//contenedor de errores en mysql
        $stor = [];//contenedor de sucursales coreecto
        $failstor = [];//contenedor de sucursales sin conexion
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->get();//se obtienen sucursales de mysql
        $provider = "SELECT CODPRO,CCOPRO,NOFPRO,NOCPRO,DOMPRO,POBPRO,CPOPRO,PROPRO,TELPRO,FALPRO,DOCPRO,IFIPRO,FUMPRO FROM F_PRO WHERE FUMPRO >= #".$date."#";//query para sacar provedores creados o modificados a partir de la fecha recibida
        $exec = $this->conn->prepare($provider);
        $exec -> execute();
        $filaspro=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($filaspro){//se comprueba que haya proveedores  on el rango de fecha
            $colsTab = array_keys($filaspro[0]);//llaves de el arreglo 
            foreach($filaspro as $row){//inicia foreach de los proveedores
                foreach($colsTab as $col){ $row[$col] = utf8_encode($row[$col]); }//foreach para codificar correctamente las filas
                $prov[] = $row;//se preparan los datos para enviar a sucursales
                $providers = [//arreglo de proveedores para mysql
                    "id"=>$row['CODPRO'],
                    "fiscal_name"=>$row['NOFPRO'],
                    "address"=> json_encode([
                        "domicilio"=>$row['DOMPRO'],
                        "poblacion"=>$row['DOMPRO'],
                        "codigo_postal"=>$row['DOMPRO'],
                        "delegacion"=>$row['DOMPRO'],
                    ])
                ];
                $provims = DB::table('providers')->where('id',$row['CODPRO'])->first();//se busca en la base de datos de mysql si existen registros con ese id
                if($provims){//si existen proveedores en mysql con ese id
                    $providers["id"]=$provims->id;//se les otorga el id a el arreglo de proveedores
                    $updtms = DB::table('providers')->where('id',$row['CODPRO'])->update($providers);//y se actualizan los campos de los proveedores
                    $update[]= "Proveedor ".$provims->id." ".$provims->fiscal_name." actualizado";//se guarda en el contenedor de los proveedores actualizados
                }else{//si no existen
                $insert = DB::table('providers')->insert($providers);//se inserta el arreglo de los proveedores
                if($insert){//si inserta
                    $created[] = "Proveedor ".$providers["id"]." ".$providers["fiscal_name"]." creado correctamente";//se almacenan datos en el contenedor de creados de mysql
                    }else{$fail[] = $providers;}//en caso contrario los almacena en fallidos de mysql
                }
            }//termino de foreach de proovedores
            foreach($stores as $store){//inicio de foreach de sucursales
                $url = $store->local_domain."/Addicted/public/api/providers/reply";//se optiene el inicio del dominio de la sucursal
                $ch = curl_init($url);//inicio de curl
                $data = json_encode(["provider" => $prov]);//se codifica el arreglo de los proveedores
                //inicio de opciones de curl
                curl_setopt($ch,CURLOPT_POSTFIELDS,$data);//se envia por metodo post
                curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                //fin de opciones e curl
                $exec = curl_exec($ch);//se executa el curl
                $exc = json_decode($exec);//se decodifican los datos decodificados
                if(is_null($exc)){//si me regresa un null
                    $failstor[] =$store->alias;//la sucursal se almacena en sucursales fallidas
                }else{
                    $stor [] = $store->alias;//de lo contrario se almacenan en sucursales
                }
                curl_close($ch);//cirre de curl
            }//fin de foreach de sucursales
            return response()->json([//retorno de termino en procesos
                "sucursales"=>[
                    "goals"=>$stor,
                    "fails"=>$failstor
                ],
                "fssucursles"=>$exc,
                "mysql"=>[
                    "creados"=>$created,
                    "actualizados"=>$update,
                    "error"=>$fail
                ]
            ]);
        }else{return response()->json("No se encontraron proveedores",404);}//se retorna mensaje en caso de no haber proveedores
    }
}
