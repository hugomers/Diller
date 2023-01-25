<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentsController extends Controller
{
    public function __construct(){
        $access = env("ACCESS_FILE");//conexion a access de sucursal
        if(file_exists($access)){
        try{  $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
    }

    // public function msgwhat($msg){
    //     $instance = env('ID_INSTANCE');
    //     $token = env('TOKEN_ULTRAMSG');
    //     $curl = curl_init();

    //     curl_setopt_array($curl, array(
    //     CURLOPT_URL => "https://api.ultramsg.com/$instance/messages/chat",
    //     CURLOPT_RETURNTRANSFER => true,
    //     CURLOPT_ENCODING => "",
    //     CURLOPT_MAXREDIRS => 10,
    //     CURLOPT_TIMEOUT => 30,
    //     CURLOPT_SSL_VERIFYHOST => 0,
    //     CURLOPT_SSL_VERIFYPEER => 0,
    //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //     CURLOPT_CUSTOMREQUEST => "POST",
    //     CURLOPT_POSTFIELDS => "token=$token&to=+5573461022&body=$msg&priority=1&referenceId=",
    //     CURLOPT_HTTPHEADER => array(
    //         "content-type: application/x-www-form-urlencoded"
    //     ),
    //     ));
    //     $response = curl_exec($curl);
    //     $err = curl_error($curl);
    //     curl_close($curl);
    //     if ($err) {
    //     return  "cURL Error #:" . $err;
    //     } else {
    //     return "Se ha notificado el alta de los agentes nuevos";
    //     }
    // }

    public function replyAgents(Request $request){
        $failstor = [];
        $stor = [];
        $date = $request->date;
        $age  = "SELECT * FROM F_AGE WHERE FALAGE = #".$date."#";
        $exec = $this->conn->prepare($age);
        $exec -> execute();
        $agentes=$exec->fetchall(\PDO::FETCH_ASSOC);
        $colsTab = array_keys($agentes[0]);//llaves de el arreglo 
            foreach($agentes as $agente){
                foreach($colsTab as $col){ $agente[$col] = utf8_encode($agente[$col]); }
                $agk [] = $agente['CODAGE'].": *".$agente ['NOMAGE']."*";
                $id [] = $agente['CODAGE'];
                $reage []= $agente;
            }
        $dep  = "SELECT * FROM T_DEP WHERE AGEDEP IN (".implode(",",$id).")";
        $exec = $this->conn->prepare($dep);
        $exec -> execute();
        $dependientes=$exec->fetchall(\PDO::FETCH_ASSOC);
        // return $dep;
        $utf = array_keys($dependientes[0]);//llaves de el arreglo 
        foreach($dependientes as $dependiente){
            foreach($utf as $asu){ $dependiente[$asu] = utf8_encode($dependiente[$asu]); }
            $redep []= $dependiente;
        }
       
        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->where('_price_type', 1)->get();//se obtienen sucursales de mysql
        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/agents/replyAgents";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            $data = json_encode(["agentes" => $reage,"dependientes" => $redep]);//se codifica el arreglo de los proveedores
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
                $failstor[] =$store->alias." Sin conexion";//la sucursal se almacena en sucursales fallidas
            }else{
                $stor [] = ["sucursal"=>$store->alias, "mssg"=>$exc];//de lo contrario se almacenan en sucursales
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
        // $whats = $this->msgwhat("Se dieron de alta los siguientes agentes: (".implode(", ",$agk).") favor de confirmar");

        $res = [
            "fail"=>$failstor,
            "chidos"=>$stor
        ];
        return response()->json($res);

    }

}
