<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    public function __construct(){
        $access = env("ACCESS_FILE");//conexion a access de cedis
        if(file_exists($access)){
        try{  $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
    }

    public function index(){
        $gvapp = [];//contenedor de gvapp
        $cedis = [];//contenedor cedis
        $sucursales = [];//contenedor sucursales


        $proced = "SELECT CODART, DESART as PRODUCTOS FROM F_ART";//se crea query para contar en access de cedis
        $exec = $this->conn->prepare($proced);
        $exec -> execute();
        $fil=$exec->fetchall(\PDO::FETCH_ASSOC);
        $cpro = count($fil);
        $cedis[]= intval($cpro);//se guarda en el contenedor de cedis

        $proms = DB::table('products')->count();//se cuentan los articulos existentes en gvapp
        $gvapp[] = $proms;//se guarda el resultado en el contenedor

        $stores = DB::table('stores')->where('_state', 1)->where('_type', 2)->get();//se obtienen sucursales de mysql

        foreach($stores as $store){//inicio de foreach de sucursales
            $url = $store->local_domain."/Addicted/public/api/products/index";//se optiene el inicio del dominio de la sucursal
            $ch = curl_init($url);//inicio de curl
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            //fin de opciones e curl
            $exec = curl_exec($ch);//se executa el curl
            $exc = json_decode($exec);//se decodifican los datos recibidos
            if(is_null($exc)){//validamos lo recibidos
                $sucursales[] =$store->alias." Sin Conexion";//en caso de null le decimos a la aplicacion que no hubo conexion
            }else{
                $sucursales[] = $store->alias." ".$exc;//si hubo conexion mostramos cuantos articulos tiene cada una de las tiendas
            }
            curl_close($ch);//cirre de curl
        }//fin de foreach de sucursales
        $products = [
            "cedis"=>$cedis,
            "gvapp"=>$gvapp,
            "sucursales"=>$sucursales
        ];
        return response()->json($products);

    }
}
