<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientsController extends Controller
{
    public function __construct(){
        $access = env("ACCESS_FILE");//conexion a access de cedis
        if(file_exists($access)){
        try{  $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
    }
    
    public function index(){
        $mysql = [];
        $factusol = [];
        $inex = [
            "fs"=>[],
            "ms"=>[]
        ];

        $mysqlcli = DB::table('clients')->get();
        foreach($mysqlcli as $client){
            $climy = [
                "codigo"=>$client->id,
                "nombre"=>$client->name
            ];
            $mysql[]= $climy;
            $idsms[] = $client->id;   
        }
        $sqlaccess = "SELECT CODCLI, NOFCLI FROM F_CLI WHERE CODCLI NOT IN (".implode(",",$idsms).")";
        $exec = $this->conn->prepare($sqlaccess);
        $exec -> execute();
        $fil=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($fil){//se comprueba que haya filas
            $colsTab = array_keys($fil[0]);
            foreach($fil as $cli){foreach($colsTab as $col){ $cli[$col] = utf8_encode($cli[$col]); }
            $clie = [//se solicita codigo y nombre
                "codigo"=>$cli['CODCLI'],
                "nombre"=>$cli['NOFCLI'],
            ];
            $inex["ms"][] =$clie;// se guarda en contenedor de faltantes de mysql
        }
        }else{$inex["ms"][]=null;}//en caso de no haber me devueve un nulo

        $sql = "SELECT CODCLI, NOFCLI FROM F_CLI";
        $exec = $this->conn->prepare($sql);
        $exec -> execute();
        $filascli=$exec->fetchall(\PDO::FETCH_ASSOC);
        $colsTab = array_keys($filascli[0]);
        foreach($filascli as $clients){foreach($colsTab as $col){ $clients[$col] = utf8_encode($clients[$col]); }
            $clifs = [//se obtiene codigo y nombre
                "codigo"=>$clients['CODCLI'],
                "nombre"=>$clients['NOFCLI']
            ];
            $factusol[]=$clifs;//se guarda en contenedro de mysql
            $idsfs[]=intval($clients['CODCLI']);//se obtienen los id de proveedores existentes en factusol
        }
        $mysq = DB::table('clients')->whereNotIn('id',$idsfs)->get();//queru para saber que proveedores no estan en factusol
        if(count($mysq) > 0){//se cuenta que haya mayor a 0 
        foreach($mysq as $notcli){
            $notcl = [//en caso de haber mas de 0 se obtiene codigo y nombre
                "codigo"=>$notcli->id,
                "nombre"=>$notcli->name
            ];
            $inex["fs"][] =$notcl;//se guardan en contenedor de faltantes en factusol
        }
        }else{$inex["fs"][] =null;}//en caso de no haber devuelve nulo

        $res = [//arreglo de respuesta
            "factusol" =>$factusol,
            "mysql"=>$mysql,
            "inexistente"=>$inex
        ];
        return response()->json($res,200);
    }

    public function refreshClients(){//SE ELIMINAN Y SE INSERTAN EN MYSQL TAL CUAL ESTA EN FACTUSOL
        
        $created = [];//cpntenedor para guardar datos creados
        $fail = [];//contenedo para guardar datos fallidos
        DB::statement("SET SQL_SAFE_UPDATES = 0;");//se desactiva safe update
        DB::statement("SET FOREIGN_KEY_CHECKS = 0;");//se desactivan las llaves foraneas
        $delete = DB::table('clients')->truncate();//se vacia la tabla de proveedores
        DB::statement("SET SQL_SAFE_UPDATES = 1;");//se activan las llaves foraneas
        DB::statement("SET FOREIGN_KEY_CHECKS = 1;");//se activa safe update
        $sql = "SELECT CODCLI, NOFCLI, DOMCLI, POBCLI, CPOCLI, PROCLI, TELCLI, FALCLI, IDETFI, FPACLI, TARCLI, TCLCLI, NVCCLI FROM F_CLI LEFT JOIN T_TFI ON T_TFI.CLITFI = F_CLI.CODCLI";//query selector de proveedores
        $exec = $this->conn->prepare($sql);
        $exec -> execute();
        $filascli=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($filascli){//si hay filas creadas
            $colsTab = array_keys($filascli[0]);//llaves
            foreach($filascli as $client){foreach($colsTab as $col){ $client[$col] = utf8_encode($client[$col]); }//se codifican bien las llaves
                $payment = DB::table('payment_methods')->where('alias',$client['FPACLI'])->value('id');
                $type = DB::table('client_types')->where('alias',$client['TCLCLI'])->value('id');
                $state = $client['NVCCLI'] == 0 ? 1 : 2;
                $phone = $client['TELCLI'] == null ? null : $client['TELCLI'];
                $clients = [//arreglo proveedores para mysql
                    "id"=>$client['CODCLI'],
                    "name"=>$client['NOFCLI'],
                    "address"=>$client['DOMCLI']." COL. ".$client['POBCLI']." C.P. ".$client['CPOCLI']." DEL. ".$client['PROCLI'],
                    "celphone"=>$phone,
                    "phone"=>null,
                    "RFC"=>null,
                    "created_at"=>$client['FALCLI'],
                    "updated_at"=>now(),
                    "barcode"=>$client['IDETFI'],
                    "_payment"=>$payment,
                    "_rate"=>$client['TARCLI'],
                    "_type"=>$type,
                    "_state"=>$state,
                    ];
                $insert = DB::table('clients')->insert($clients);//se inserta en la tabla de proveedores
                if($insert){//si es insertado
                $created[] = $clients;//se guardan en los creados
                }else{$fail[] = $client;}// en caso que no se guarda en fallidos
            }
            $response = [//se crea la respuesta
                "created"=>$created,
                "failed"=>$fail
            ];
            return response()->json($response,201);
        }else{return response()->json("No se encontraron proveedores",404);}
    }

    public function replyClient(Request $request){//replicador de proveedores
        $date = $request->date;//se recibe fecha
        $created = [];//contenedor de creads en mysql
        $update = [];//contenedor de actualizados en mysql
        $fail = [];//contenedor de errores en mysql
        $stor = [];//contenedor de sucursales coreecto
        $failstor = [];//contenedor de sucursales sin conexion
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->get();//se obtienen sucursales de mysql
        $client = "SELECT CODCLI,CCOCLI,NIFCLI,NOFCLI,NOCCLI,DOMCLI,POBCLI,CPOCLI,PROCLI,TELCLI,AGECLI,FPACLI,TARCLI,TCLCLI,FALCLI,NVCCLI,DOCCLI,IFICLI,FUMCLI,IDETFI FROM F_CLI LEFT JOIN T_TFI ON T_TFI.CLITFI = F_CLI.CODCLI   WHERE FUMCLI >= #".$date."#";//query para sacar provedores creados o modificados a partir de la fecha recibida
        $exec = $this->conn->prepare($client);
        $exec -> execute();
        $filaspro=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($filaspro){//se comprueba que haya proveedores  on el rango de fecha
            $colsTab = array_keys($filaspro[0]);//llaves de el arreglo 
            foreach($filaspro as $row){//inicia foreach de los proveedores
                foreach($colsTab as $col){ $row[$col] = utf8_encode($row[$col]); }//foreach para codificar correctamente las filas
                $cli[] = $row;//se preparan los datos para enviar a sucursales
                $payment = DB::table('payment_methods')->where('alias',$row['FPACLI'])->value('id');
                $type = DB::table('client_types')->where('alias',$row['TCLCLI'])->value('id');
                $state = $row['NVCCLI'] == 0 ? 1 : 2;
                $phone = $row['TELCLI'] == null ? null : $row['TELCLI'];
                $clients = [//arreglo proveedores para mysql
                    "id"=>$row['CODCLI'],
                    "name"=>$row['NOFCLI'],
                    "address"=>$row['DOMCLI']." COL. ".$row['POBCLI']." C.P. ".$row['CPOCLI']." DEL. ".$row['PROCLI'],
                    "celphone"=>$phone,
                    "phone"=>null,
                    "RFC"=>null,
                    "created_at"=>$row['FALCLI'],
                    "updated_at"=>now(),
                    "barcode"=>$row['IDETFI'],
                    "_payment"=>$payment,
                    "_rate"=>$row['TARCLI'],
                    "_type"=>$type,
                    "_state"=>$state,
                    ];
                $clims = DB::table('clients')->where('id',$row['CODCLI'])->first();//se busca en la base de datos de mysql si existen registros con ese id
                if($clims){//si existen proveedores en mysql con ese id
                    $clients["id"]=$clims->id;//se les otorga el id a el arreglo de proveedores
                    $updtms = DB::table('clients')->where('id',$row['CODCLI'])->update($clients);//y se actualizan los campos de los proveedores
                    $update[]= "Cliente ".$clims->id." ".$clims->name." actualizado";//se guarda en el contenedor de los proveedores actualizados
                }else{//si no existen
                $insert = DB::table('clients')->insert($clients);//se inserta el arreglo de los proveedores
                if($insert){//si inserta
                    $created[] = "Cliente ".$clients["id"]." ".$clients["name"]." creado correctamente";//se almacenan datos en el contenedor de creados de mysql
                    }else{$fail[] = $clients;}//en caso contrario los almacena en fallidos de mysql
                }
            }//termino de foreach de proovedores
            foreach($stores as $store){//inicio de foreach de sucursales
                $url = $store->local_domain."/Addicted/public/api/clients/reply";//se optiene el inicio del dominio de la sucursal
                $ch = curl_init($url);//inicio de curl
                $data = json_encode(["client" => $cli]);//se codifica el arreglo de los proveedores
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
