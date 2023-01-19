<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriesController extends Controller
{
    public function __construct(){
        $access = env("ACCESS_FILE");//conexion a access de sucursal
        if(file_exists($access)){
        try{  $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
    }

    public function index(){
        $msseccion =[];
        $msfamilia =[];
        $mscategory =[];
        $fam = "SELECT DISTINCT F_SEC.DESSEC, F_FAM.DESFAM, F_ART.CP1ART FROM ((F_SEC INNER JOIN F_FAM ON F_FAM.SECFAM = F_SEC.CODSEC) INNER JOIN F_ART ON F_ART.FAMART = F_FAM.CODFAM)";
        $exec = $this->conn->prepare($fam);
        $exec -> execute();
        $cate=$exec->fetchall(\PDO::FETCH_ASSOC);
        $colsTab = array_keys($cate[0]);//llaves de el arreglo 
        foreach($cate as $categoria){
            foreach($colsTab as $col){ $categoria[$col] = utf8_encode($categoria[$col]); }
            $seccion [] = $categoria['DESSEC'];
            $familia [] = $categoria['DESFAM'];
            $catego [] = $categoria['CP1ART'];
        }
        $factusol = [
            "seccion"=>array_unique($seccion),
            "familia"=>array_unique($familia),
            "categoria"=>array_unique($catego)
        ];

        $caty = DB::table('product_categories')->get();
        foreach($caty as $mscat){
            if($mscat->deep == 0){
                $msseccion [] = $mscat->name;
            }elseif($mscat->deep == 1){
                $msfamilia [] = $mscat->name;
            }elseif($mscat->deep == 2){
                $mscategory[]= $mscat->name;
            }
        }
        $msql = [
            "seccion"=>array_unique($msseccion),
            "familia"=>array_unique($msfamilia),
            "categoria"=>array_unique($mscategory)
        ];
        $res = [
            "factusol"=>$factusol,
            "mysql"=>$msql
        ];


        return response()->json($res);
    }

}
