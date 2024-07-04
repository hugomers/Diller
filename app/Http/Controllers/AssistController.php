<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rats\Zkteco\Lib\ZKTeco;

class AssistController extends Controller
{
    public function createUser(Request $request){//CREACION DE USUARIO DENTRO DEL CHECADOR DE REGISTRO
        $zkteco = env('ZKTECO_DEVICE');
        $user = DB::table('users')->where('id',$request->id)->first();

        if($user){
            $zk = new ZKTeco($zkteco);
            if($zk->connect()){
                $zkuser =  $zk->getUser();
                foreach($zkuser as $usma){
                    $userid[] = $usma['userid'];
                    $uid[]=$usma['uid'];
                }
                $maxus = max($userid) +1;
                $uid = max($uid) + 1;

                $name = $user->name." ".$user->surnames;
                $register = substr($name,0,24);

                $anadir = $zk->setUser($uid, $maxus, $register,'');
                $msidrc = DB::table('users')->where('id',$request->id)->update(['RC_id'=>$maxus]);

                $user = [
                    "idchecador"=>$maxus,
                    "nombre"=>$register,
                ];

                return response()->json($user,201);
            }else{return response()->json("No hay conexion a el checador",501);}
        }else{return response()->json("No se a encontrado ningun usuaio con el id ".$request->id,404);}

    }

    public function replyAssist(){
        $zkteco = env('ZKTECO_DEVICE');
        $zk = new ZKTeco($zkteco);
        if($zk->connect()){
            $assists = $zk->getAttendance();
            if($assists){
                foreach($assists as $assist){
                    $serie = ltrim(stristr($zk->serialNumber(),'='),'=');
                    $sucursal = DB::table('assist_devices')->where('serial_number',$serie)->first();
                    $user = DB::table('users')->where('RC_id',intval($assist['id']))->value('id');
                    $report = [
                    "auid" => $assist['uid'],//id checada checador
                    "register" => $assist['timestamp'], //horario
                    "_user" => $user,//id del usuario
                    "_store"=> $sucursal->_store,
                    "_type"=>$assist['type'],//entrada y salida
                    "_class"=>$assist['state'],
                    "_device"=>$sucursal->id,
                    ];
                    $insert = DB::table('assists')->insert($report);
                }
                $zk -> clearAttendance();
                return response()->json($report,201);
            }else{return response()->json("No hay registros por el momento",404);}
        }else{return response()->json("No hay conexion a el checador",501);}

    }

    public function addDevices(Request $request){
        $zk = new ZKTeco($request->ip);
        $store = DB::table('stores')->where('alias',$request->store)->value('id');
        if($zk->connect()){
            if($store){
                $ip = DB::table('assist_devices')->where('ip',$request->ip)->first();
                if($ip == false){
                        $serie = ltrim(stristr($zk->serialNumber(),'='),'=');
                        $dev = DB::table('assist_devices')->where('serial_number',$serie)->first();
                        if($dev == false){
                            $name = ltrim(stristr($zk->deviceName(),'='),'=');
                            $device = [
                                "name"=>$name,
                                "nick_name"=>$request->nick,
                                "serial_number"=>$serie,
                                "_store"=>$store,
                                "ip"=>$request->ip
                            ];
                            $insert = DB::table('assist_devices')->insert($device);
                            return response()->json(["msg"=>"insertado correctamente","dispositivo"=>$device],201);
                        }else{return response()->json("El numero de serie ya existe en el registro de el checador ".$dev->nick_name,404);}

                }else{return response()->json("La direccion IP ya se encuentra registrada en el reloj checador ".$ip->nick_name,404);}
            }else{return response()->json("No existe ninguna sucursal con el alias ".$request->store,404);}
        }else{return response()->json("No hay conexion a el checador",501);}
    }
}
