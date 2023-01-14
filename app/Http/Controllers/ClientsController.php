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
        $mysql = [];//contenedor mysql 
        $factusol = [];//contenedor factusol
        $inex = [//contenedor de inexistencias
            "fs"=>[],//inexistencias en factusol   
            "ms"=>[]//inexistencias en mysql
        ];

        $mysqlcli = DB::table('clients')->get();//se obtienen todos los clientes
        foreach($mysqlcli as $client){//comienso de foreach de clientes
            $climy = [//contenedor de clientes
                "codigo"=>$client->id,//se obtiene el id del cliente
                "nombre"=>$client->name//se obtiene el nombre de el cliente
            ];
            $mysql[]= $climy;//se guardan en el contenedor de mysql
            $idsms[] = $client->id;//se obtienen solo los id de clientes   
        }//termino foreach clientes
        $sqlaccess = "SELECT CODCLI, NOFCLI FROM F_CLI WHERE CODCLI NOT IN (".implode(",",$idsms).")";//query para buscar cuales clientes le hacen falta a mysql
        $exec = $this->conn->prepare($sqlaccess);
        $exec -> execute();
        $fil=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($fil){//se comprueba que haya filas
            $colsTab = array_keys($fil[0]);
            foreach($fil as $cli){foreach($colsTab as $col){ $cli[$col] = utf8_encode($cli[$col]); }
            $clie = [//contenedor cliente
                "codigo"=>$cli['CODCLI'],//se obtiene el id de el cliente en factusol
                "nombre"=>$cli['NOFCLI'],//se obtiene el nombre de el cliente en factusol
            ];
            $inex["ms"][] =$clie;//el resultado se guara en el contenedor de inexistentes de mysql
        }
        }else{$inex["ms"][]=null;}//en caso de no haber se envia nulo

        $sql = "SELECT CODCLI, NOFCLI FROM F_CLI";//query para mostrar todos los clientes de factusol
        $exec = $this->conn->prepare($sql);
        $exec -> execute();
        $filascli=$exec->fetchall(\PDO::FETCH_ASSOC);
        $colsTab = array_keys($filascli[0]);
        foreach($filascli as $clients){foreach($colsTab as $col){ $clients[$col] = utf8_encode($clients[$col]); }//foreach de clientes
            $clifs = [//contenedor de clientes factusol 
                "codigo"=>$clients['CODCLI'],//se obtiene el id de el cliente en factusol
                "nombre"=>$clients['NOFCLI']//nombre de cliente factusol
            ];
            $factusol[]=$clifs;//se guarda el resultado en el contenedor de factusl
            $idsfs[]=intval($clients['CODCLI']);//se obtienen los id de los clientes de factusol
        }
        $mysq = DB::table('clients')->whereNotIn('id',$idsfs)->get();//se ejecuta el query para saber que clientes no estan en factusol
        if(count($mysq) > 0){//si  devuelve mas de 0 filas
        foreach($mysq as $notcli){//se crea el foreach de clientes de mysql
            $notcl = [//se guarda en el contenedor de clientes mysql
                "codigo"=>$notcli->id,//se obtiene el id
                "nombre"=>$notcli->name//se obtiene el nombre
            ];
            $inex["fs"][] =$notcl;//se guardan en contenedor de inexistentes en factusol
        }
        }else{$inex["fs"][] =null;}//en caso de no haber devuelve nul

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
        $delete = DB::table('clients')->truncate();//se vacia la tabla de clientes
        DB::statement("SET SQL_SAFE_UPDATES = 1;");//se activan las llaves foraneas
        DB::statement("SET FOREIGN_KEY_CHECKS = 1;");//se activa safe update
        $sql = "SELECT CODCLI, NOFCLI, DOMCLI, POBCLI, CPOCLI, PROCLI, TELCLI, FALCLI, IDETFI, FPACLI, TARCLI, TCLCLI, NVCCLI FROM F_CLI LEFT JOIN T_TFI ON T_TFI.CLITFI = F_CLI.CODCLI ORDER BY F_CLI.CODCLI ASC";//query selector de clientes
        $exec = $this->conn->prepare($sql);
        $exec -> execute();
        $filascli=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($filascli){//si hay filas creadas
            $colsTab = array_keys($filascli[0]);//llaves
            foreach($filascli as $client){foreach($colsTab as $col){ $client[$col] = utf8_encode($client[$col]); }//se codifican bien las llaves
                $payment = DB::table('payment_methods')->where('alias',$client['FPACLI'])->value('id');//se obtiene el id de mysql de las formas de pago
                $type = DB::table('client_types')->where('alias',$client['TCLCLI'])->value('id');//se obtiene el id de el tipo de cliente de mysql
                $state = $client['NVCCLI'] == 0 ? 1 : 2;//en caso deque el campo de factusol "no vender a cliente" devuelva 0 el estado en mysql sera 1 de lo contrario sera 2
                $phone = $client['TELCLI'] == null ? null : $client['TELCLI'];//se comprueba si es nulo el telefono
                $clients = [//arreglo proveedores para mysql
                    "FS_id"=>$client['CODCLI'],//codigo cliente factusol
                    "name"=>$client['NOFCLI'],//nombre cliente factusol
                    "address"=>$client['DOMCLI']." COL. ".$client['POBCLI']." C.P. ".$client['CPOCLI']." DEL. ".$client['PROCLI'],//domicilio cliente factusol
                    "celphone"=>$phone,//telefono cliente
                    "phone"=>null,
                    "RFC"=>null,
                    "created_at"=>$client['FALCLI'],//fecha creacion de factusol
                    "updated_at"=>now(),//fecha actualizacion mysql
                    "barcode"=>$client['IDETFI'],//codigo de barras de cliente
                    "_payment"=>$payment,//forma de pago 
                    "_rate"=>$client['TARCLI'],//tarifa otorgada en cliente
                    "_type"=>$type,//tipo de clinte
                    "_state"=>$state,//estado de cliente
                    ];
                $insert = DB::table('clients')->insert($clients);//se inserta en la tabla de clientes
                if($insert){//si es insertado
                $created[] = $clients;//se guardan en los creados
                }else{$fail[] = $client;}// en caso que no se guarda en fallidos
            }
            $response = [//se crea la respuesta
                "created"=>$created,
                "failed"=>$fail
            ];
            return response()->json($response,201);
        }else{return response()->json("No se encontraron clientes",404);}
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
            foreach($filaspro as $row){//inicia foreach de los clietes
                foreach($colsTab as $col){ $row[$col] = utf8_encode($row[$col]); }//foreach para codificar correctamente las filas
                $cli[] = $row;//se preparan los datos para enviar a sucursales
                $payment = DB::table('payment_methods')->where('alias',$row['FPACLI'])->value('id');//se obtiene el metodo de pago del clientes
                $type = DB::table('client_types')->where('alias',$row['TCLCLI'])->value('id');
                $state = $row['NVCCLI'] == 0 ? 1 : 2;//se verifica el status del cliente
                $phone = $row['TELCLI'] == null ? null : $row['TELCLI'];//se verifica el telefono del cliente
                $clients = [//arreglo clientes para mysql
                    "FS_id"=>$row['CODCLI'],//codigo de cliente
                    "name"=>$row['NOFCLI'],//nombre del cliente
                    "address"=>$row['DOMCLI']." COL. ".$row['POBCLI']." C.P. ".$row['CPOCLI']." DEL. ".$row['PROCLI'],//domicilio de cliente
                    "celphone"=>$phone,//telefono cliente
                    "phone"=>null,
                    "RFC"=>null,
                    "created_at"=>$row['FALCLI'],//alta cliente
                    "updated_at"=>now(),//actualizacion cliente
                    "barcode"=>$row['IDETFI'],//codigo de barras cliente
                    "_payment"=>$payment,//forma de pago predeterminada
                    "_rate"=>$row['TARCLI'],//tarifa otorgada a cliente
                    "_type"=>$type,//tipo de cliente
                    "_state"=>$state,//stado de cliente
                    ];
                $clims = DB::table('clients')->where('FS_id',$row['CODCLI'])->first();//se busca en la base de datos de mysql si existen registros con ese id
                if($clims){//si existen cliente en mysql con ese id
                    $clients["FS_id"]=$clims->FS_id;//se les otorga el id a el arreglo de clientes
                    $updtms = DB::table('clients')->where('FS_id',$row['CODCLI'])->update($clients);//y se actualizan los campos de los clientes
                    $update[]= "Cliente ".$clims->FS_id." ".$clims->name." actualizado";//se guarda en el contenedor de los clientes actualizados
                }else{//si no existen
                $insert = DB::table('clients')->insert($clients);//se inserta el arreglo de los clientes
                if($insert){//si inserta
                    $created[] = "Cliente ".$clients["FS_id"]." ".$clients["name"]." creado correctamente";//se almacenan datos en el contenedor de creados de mysql
                    }else{$fail[] = $clients;}//en caso contrario los almacena en fallidos de mysql
                }
            }//termino de foreach de proovedores
            foreach($stores as $store){//inicio de foreach de sucursales
                $url = $store->local_domain."/Addicted/public/api/clients/reply";//se optiene el inicio del dominio de la sucursal
                $ch = curl_init($url);//inicio de curl
                $data = json_encode(["client" => $cli]);//se codifica el arreglo de los clientes
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
        }else{return response()->json("No se encontraron clientes",404);}//se retorna mensaje en caso de no haber clientes
    }

    public function conditionSpecial(Request $request){
        $client = $request->client;
        $mysql=[];
        $fail =[];
        $failstor=[];
        $stor=[];
        DB::statement("SET SQL_SAFE_UPDATES = 0;");//se desactiva safe update
        DB::statement("SET FOREIGN_KEY_CHECKS = 0;");//se desactivan las llaves foraneas
        $delete = DB::table('especial_customer_condition')->where('_client',$client)->delete();//se vacia la tabla de clientes
        DB::statement("SET SQL_SAFE_UPDATES = 1;");//se activan las llaves foraneas
        DB::statement("SET FOREIGN_KEY_CHECKS = 1;");//se activa safe update
        $clientms = "SELECT * FROM F_PRC WHERE CLIPRC = $client";
        $exec = $this->conn->prepare($clientms);
        $exec -> execute();
        $fil=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($fil){
            foreach($fil as $con){
                $special[] = $con;
                $ids = DB::table('clients')->where('FS_id',$con['CLIPRC'])->value('id');
                $idproduc = DB::table('products')->where('code',$con['ARTPRC'])->value('id');
                if($idproduc){
                    $insms = [
                        "_client"=>$ids,
                        "_product"=>$idproduc,
                        "price"=>$con['PREPRC']
                    ];

                    $insertms = DB::table('especial_customer_condition')->insert($insms);
                }else{$fail[]="No existe el producto ".$con['ARTPRC'];}
            }
            if($insertms){$mysql[]="Guardado con exito";}else{$mysql[]="Error en el registro";}
            
            $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type',1)->get();
            foreach($stores as $store){//inicio de foreach de sucursales
                $url = $store->local_domain."/Addicted/public/api/clients/conditionSpecial";//se optiene el inicio del dominio de la sucursal
                $ch = curl_init($url);//inicio de curl
                $data = json_encode(["special" => $special,"client"=>$client]);//se codifica el arreglo de los clientes
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
                    $failstor[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
                }else{
                    $stor [] = ["sucursal"=>$store->alias, "mssg"=>$exc];//de lo contrario se almacenan en sucursales
                }
                curl_close($ch);//cirre de curl
            }//fin de foreach de sucursales
            $res = [
                "mysql"=>[
                    "goals"=>$mysql,
                    "fail"=>$fail
                ],
                "sucursal"=>[
                    "goals"=>$stor,
                    "fail"=>$failstor
                ]
                ];
                return response()->json($res);
        }else{return response()->json("El numero de cliente no tiene precios especiales");}


    }

    public function refreshLoyaltyCard(){
        $failstor= [];
        $stor =[];

        $select = "SELECT * FROM T_TFI";
        $exec = $this->conn->prepare($select);
        $exec -> execute();
        $fil=$exec->fetchall(\PDO::FETCH_ASSOC);
        if($fil){
            foreach($fil as $row){
                $req[] = $row;
            }
            $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->get();
            foreach($stores as $store){//inicio de foreach de sucursales
                $url = $store->local_domain."/Addicted/public/api/clients/refreshLoyaltyCard";//se optiene el inicio del dominio de la sucursal
                $ch = curl_init($url);//inicio de curl
                $data = json_encode(["tarjetas" => $req]);//se codifica el arreglo de los clientes
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
                    $failstor[] =$store->alias." sin conexion";//la sucursal se almacena en sucursales fallidas
                }else{
                    $stor [] = ["sucursal"=>$store->alias, "mssg"=>$exc];//de lo contrario se almacenan en sucursales
                }
                curl_close($ch);//cirre de curl
            }//fin de foreach de sucursales
            $res = [
                "sucursal"=>[
                    "goals"=>$stor,
                    "fail"=>$failstor
                ]
                ];
                return response()->json($res);
        }else{ return response()->json("No existen tarjetas de fidelizacion");}
        
    }
}
