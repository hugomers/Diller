<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

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

    // public function test(){
    //     $articulos = [];
    //     $workpoint = env('WORKPOINT');
    //     $sto = "SELECT 
    //     F_STO.ARTSTO,
    //     F_STO.ALMSTO,
    //     F_STO.ACTSTO,
    //     F_STO.DISSTO 
    //     FROM ((F_STO
    //     INNER JOIN F_LFA ON F_LFA.ARTLFA  = F_STO.ARTSTO)
    //     INNER JOIN F_FAC ON ( F_FAC.TIPFAC&'-'&F_FAC.CODFAC = F_LFA.TIPLFA&'-'&F_LFA.CODLFA AND F_FAC.FECFAC =DATE()) )
    //     UNION 
    //     SELECT 
    //     F_STO.ARTSTO,
    //     F_STO.ALMSTO,
    //     F_STO.ACTSTO,
    //     F_STO.DISSTO 
    //     FROM ((F_STO
    //     INNER JOIN F_LFR ON F_LFR.ARTLFR  = F_STO.ARTSTO)
    //     INNER JOIN F_FRE ON (F_FRE.TIPFRE&'-'&F_FRE.CODFRE = F_LFR.TIPLFR&'-'&F_LFR.CODLFR  AND F_FRE.FECFRE =DATE()))
    //     UNION 
    //     SELECT 
    //     F_STO.ARTSTO,
    //     F_STO.ALMSTO,
    //     F_STO.ACTSTO,
    //     F_STO.DISSTO 
    //     FROM ((F_STO
    //     INNER JOIN F_LEN ON F_LEN.ARTLEN  = F_STO.ARTSTO)
    //     INNER JOIN F_ENT ON (F_ENT.TIPENT&'-'&F_ENT.CODENT = F_LEN.TIPLEN&'-'&F_LEN.CODLEN  AND F_ENT.FECENT =DATE()))
    //     UNION 
    //     SELECT 
    //     F_STO.ARTSTO,
    //     F_STO.ALMSTO,
    //     F_STO.ACTSTO,
    //     F_STO.DISSTO 
    //     FROM ((F_STO
    //     INNER JOIN F_LFD ON F_LFD.ARTLFD  = F_STO.ARTSTO)
    //     INNER JOIN F_FRD ON (F_FRD.TIPFRD&'-'&F_FRD.CODFRD = F_LFD.TIPLFD&'-'&F_LFD.CODLFD  AND F_FRD.FECFRD =DATE()))
    //     UNION 
    //     SELECT 
    //     F_STO.ARTSTO,
    //     F_STO.ALMSTO,
    //     F_STO.ACTSTO,
    //     F_STO.DISSTO 
    //     FROM ((F_STO
    //     INNER JOIN F_LAL ON F_LAL.ARTLAL  = F_STO.ARTSTO)
    //     INNER JOIN F_ALB ON (F_ALB.TIPALB&'-'&F_ALB.CODALB = F_LAL.TIPLAL&'-'&F_LAL.CODLAL  AND F_ALB.FECALB =DATE()))
    //     UNION 
    //     SELECT 
    //     F_STO.ARTSTO,
    //     F_STO.ALMSTO,
    //     F_STO.ACTSTO,
    //     F_STO.DISSTO 
    //     FROM ((F_STO
    //     INNER JOIN F_LFB ON F_LFB.ARTLFB  = F_STO.ARTSTO)
    //     INNER JOIN F_FAB ON (F_FAB.TIPFAB&'-'&F_FAB.CODFAB = F_LFB.TIPLFB&'-'&F_LFB.CODLFB  AND F_FAB.FECFAB =DATE()))
    //     UNION 
    //     SELECT 
    //     F_STO.ARTSTO,
    //     F_STO.ALMSTO,
    //     F_STO.ACTSTO,
    //     F_STO.DISSTO 
    //     FROM ((F_STO
    //     INNER JOIN F_LSA ON F_LSA.ARTLSA  = F_STO.ARTSTO)
    //     INNER JOIN F_SAL ON (F_SAL.CODSAL = F_LSA.CODLSA  AND F_SAL.FECSAL =DATE()))
    //     UNION 
    //     SELECT 
    //     F_STO.ARTSTO,
    //     F_STO.ALMSTO,
    //     F_STO.ACTSTO,
    //     F_STO.DISSTO 
    //     FROM ((F_STO
    //     INNER JOIN F_LTR ON F_LTR.ARTLTR  = F_STO.ARTSTO)
    //     INNER JOIN F_TRA ON (F_TRA.DOCTRA = F_LTR.DOCLTR  AND F_TRA.FECTRA =DATE()))
    //     UNION 
    //     SELECT 
    //     F_STO.ARTSTO,
    //     F_STO.ALMSTO,
    //     F_STO.ACTSTO,
    //     F_STO.DISSTO 
    //     FROM (F_STO
    //     INNER JOIN F_CIN ON (F_CIN.ARTCIN  = F_STO.ARTSTO AND F_CIN.FECCIN = DATE()))
    //     UNION 
    //     SELECT 
    //     F_STO.ARTSTO,
    //     F_STO.ALMSTO,
    //     F_STO.ACTSTO,
    //     F_STO.DISSTO 
    //     FROM ((F_STO
    //     INNER JOIN F_LFC ON F_LFC.ARTLFC  = F_STO.ARTSTO)
    //     INNER JOIN F_FCO ON (F_FCO.CODFCO = F_LFC.CODLFC  AND F_FCO.FECFCO =DATE()))";
    //     $exec = $this->conn->prepare($sto);
    //     $exec -> execute();
    //     $stocks=$exec->fetchall(\PDO::FETCH_ASSOC);
        
    //     if($stocks){
    //     foreach($stocks as $stock){
    //         $update = DB::table('product_stock AS PS')
    //         ->join('products AS P','P.id','PS._product')
    //         ->join('warehouses AS W','W.id','PS._warehouse')
    //         ->where('W.alias',$stock['ALMSTO'])
    //         ->where('P.code',$stock['ARTSTO'])
    //         ->where('W._store',$workpoint)
    //         ->update(["_current"=>$stock['ACTSTO'],"available"=>$stock['DISSTO']]);
    //         }

            
    //     return response()->json($update);
    //     }else{ return response()->json("no hay stock para replicar");}
    // }

    // public function test(){//POSIBLEMENTE SEA ESTE PERO DEJA VEO
    //     $workpoint = env('WORKPOINT');
    //     $almacenes = DB::table('warehouses')->where('_store',$workpoint)->get();
    //     foreach($almacenes as $almacen){
    //         $alma = $almacen->alias;
    //         try{
    //         $sto  = "SELECT * FROM (SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LFA ON F_LFA.ARTLFA  = F_STO.ARTSTO)
    //             INNER JOIN F_FAC ON ( F_FAC.TIPFAC&'-'&F_FAC.CODFAC = F_LFA.TIPLFA&'-'&F_LFA.CODLFA AND F_FAC.FECFAC =DATE()) )
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LFR ON F_LFR.ARTLFR  = F_STO.ARTSTO)
    //             INNER JOIN F_FRE ON (F_FRE.TIPFRE&'-'&F_FRE.CODFRE = F_LFR.TIPLFR&'-'&F_LFR.CODLFR  AND F_FRE.FECFRE =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LEN ON F_LEN.ARTLEN  = F_STO.ARTSTO)
    //             INNER JOIN F_ENT ON (F_ENT.TIPENT&'-'&F_ENT.CODENT = F_LEN.TIPLEN&'-'&F_LEN.CODLEN  AND F_ENT.FECENT =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LFD ON F_LFD.ARTLFD  = F_STO.ARTSTO)
    //             INNER JOIN F_FRD ON (F_FRD.TIPFRD&'-'&F_FRD.CODFRD = F_LFD.TIPLFD&'-'&F_LFD.CODLFD  AND F_FRD.FECFRD =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LAL ON F_LAL.ARTLAL  = F_STO.ARTSTO)
    //             INNER JOIN F_ALB ON (F_ALB.TIPALB&'-'&F_ALB.CODALB = F_LAL.TIPLAL&'-'&F_LAL.CODLAL  AND F_ALB.FECALB =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LFB ON F_LFB.ARTLFB  = F_STO.ARTSTO)
    //             INNER JOIN F_FAB ON (F_FAB.TIPFAB&'-'&F_FAB.CODFAB = F_LFB.TIPLFB&'-'&F_LFB.CODLFB  AND F_FAB.FECFAB =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LSA ON F_LSA.ARTLSA  = F_STO.ARTSTO)
    //             INNER JOIN F_SAL ON (F_SAL.CODSAL = F_LSA.CODLSA  AND F_SAL.FECSAL =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LTR ON F_LTR.ARTLTR  = F_STO.ARTSTO)
    //             INNER JOIN F_TRA ON (F_TRA.DOCTRA = F_LTR.DOCLTR  AND F_TRA.FECTRA =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM (F_STO
    //             INNER JOIN F_CIN ON (F_CIN.ARTCIN  = F_STO.ARTSTO AND F_CIN.FECCIN = DATE()))
    //             UNION SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LFC ON F_LFC.ARTLFC  = F_STO.ARTSTO)
    //             INNER JOIN F_FCO ON (F_FCO.CODFCO = F_LFC.CODLFC  AND F_FCO.FECFCO =DATE()))) WHERE ALMSTO = ?";
    //         $exec = $this->conn->prepare($sto);
    //         $exec -> execute([$alma]);
    //         $stocks=$exec->fetchall(\PDO::FETCH_ASSOC);
    //         }catch (\PDOException $e){ die($e->getMessage());}
    //         if($stocks){
    //         foreach($stocks as $stock){
    //             $update = DB::table('product_stock AS PS')
    //             ->join('products AS P','P.id','PS._product')
    //             ->join('warehouses AS W','W.id','PS._warehouse')
    //             ->where('W.alias',$stock['ALMSTO'])
    //             ->where('P.code',$stock['ARTSTO'])
    //             ->where('W._store',$workpoint)
    //             ->where('_current',$stock['ACTSTO'])
    //             ->where('available',$stock['DISSTO'])
    //             ->update(["_current"=>$stock['ACTSTO'],"available"=>$stock['DISSTO']]);
    //         }
    //     }
    //     $almas[] = $almacen;
    // }
    // return response()->json($almas);
    // }

    // public function test(){
    //     $factusol = [];
    //     $msql = [];
    //     $workpoint = env('WORKPOINT');
    //     try{
    //         $sto  = "SELECT * FROM (SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LFA ON F_LFA.ARTLFA  = F_STO.ARTSTO)
    //             INNER JOIN F_FAC ON ( F_FAC.TIPFAC&'-'&F_FAC.CODFAC = F_LFA.TIPLFA&'-'&F_LFA.CODLFA AND F_FAC.FECFAC =DATE()) )
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LFR ON F_LFR.ARTLFR  = F_STO.ARTSTO)
    //             INNER JOIN F_FRE ON (F_FRE.TIPFRE&'-'&F_FRE.CODFRE = F_LFR.TIPLFR&'-'&F_LFR.CODLFR  AND F_FRE.FECFRE =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LEN ON F_LEN.ARTLEN  = F_STO.ARTSTO)
    //             INNER JOIN F_ENT ON (F_ENT.TIPENT&'-'&F_ENT.CODENT = F_LEN.TIPLEN&'-'&F_LEN.CODLEN  AND F_ENT.FECENT =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LFD ON F_LFD.ARTLFD  = F_STO.ARTSTO)
    //             INNER JOIN F_FRD ON (F_FRD.TIPFRD&'-'&F_FRD.CODFRD = F_LFD.TIPLFD&'-'&F_LFD.CODLFD  AND F_FRD.FECFRD =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LAL ON F_LAL.ARTLAL  = F_STO.ARTSTO)
    //             INNER JOIN F_ALB ON (F_ALB.TIPALB&'-'&F_ALB.CODALB = F_LAL.TIPLAL&'-'&F_LAL.CODLAL  AND F_ALB.FECALB =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LFB ON F_LFB.ARTLFB  = F_STO.ARTSTO)
    //             INNER JOIN F_FAB ON (F_FAB.TIPFAB&'-'&F_FAB.CODFAB = F_LFB.TIPLFB&'-'&F_LFB.CODLFB  AND F_FAB.FECFAB =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LSA ON F_LSA.ARTLSA  = F_STO.ARTSTO)
    //             INNER JOIN F_SAL ON (F_SAL.CODSAL = F_LSA.CODLSA  AND F_SAL.FECSAL =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LTR ON F_LTR.ARTLTR  = F_STO.ARTSTO)
    //             INNER JOIN F_TRA ON (F_TRA.DOCTRA = F_LTR.DOCLTR  AND F_TRA.FECTRA =DATE()))
    //             UNION 
    //             SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM (F_STO
    //             INNER JOIN F_CIN ON (F_CIN.ARTCIN  = F_STO.ARTSTO AND F_CIN.FECCIN = DATE()))
    //             UNION SELECT 
    //             F_STO.ARTSTO,
    //             F_STO.ALMSTO,
    //             F_STO.ACTSTO,
    //             F_STO.DISSTO 
    //             FROM ((F_STO
    //             INNER JOIN F_LFC ON F_LFC.ARTLFC  = F_STO.ARTSTO)
    //             INNER JOIN F_FCO ON (F_FCO.CODFCO = F_LFC.CODLFC  AND F_FCO.FECFCO =DATE()))) ORDER BY F_STO.ALMSTO ASC,  F_STO.ARTSTO DESC";
    //         $exec = $this->conn->prepare($sto);
    //         $exec -> execute();
    //         $stocks=$exec->fetchall(\PDO::FETCH_ASSOC);
    //     }catch (\PDOException $e){ die($e->getMessage());}
    //     if($stocks){
    //         foreach($stocks as $stock){  
    //             $almas[]=$stock['ARTSTO'];
    //             $articulos = [
    //             'code'=>strtoupper($stock['ARTSTO']),
    //             'alias'=>$stock['ALMSTO'],
    //             '_current'=>intval($stock['ACTSTO']),
    //             'available'=>intval($stock['DISSTO'])
    //             ];
    //             $factusol[]=$articulos;
    //         }

    //         $stockms = DB::table('product_stock AS PS')
    //         ->join('warehouses AS W','W.id','PS._warehouse')
    //         ->join('products AS P','P.id','PS._product')
    //         ->where('W._store',$workpoint)
    //         ->whereIn('P.code',array_unique($almas))
    //         ->select('P.code','W.alias','PS._current','PS.available')
    //         ->orderByRaw('W.alias ASC')
    //         ->orderByRaw('P.code DESC')
    //         ->get();
    //         foreach($stockms as $mss){
    //             $arti = [
    //                 'code'=>strtoupper($mss->code),
    //                 'alias'=>$mss->alias,
    //                 '_current'=>$mss->_current,
    //                 'available'=>$mss->available
    //             ];
    //             $msql[]=$arti;          
    //         }


    //         $out = array_udiff($factusol,$msql, function($a,$b){
    //             if($a == $b){
    //                 return  0;
                    
    //             }else{
    //                 return ($a > $b) ? 1 : -1;
    //             }
    //         });

    //         if($out){
    //             foreach($out as $mod){
    //                 $cool = [
    //                     "_current"=>$mod['_current'],
    //                     "available"=>$mod['available']
    //                 ];
    //             $update =  DB::table('product_stock AS PS')
    //             ->join('warehouses AS W','W.id','PS._warehouse')
    //             ->join('products AS P','P.id','PS._product')
    //             ->where('W._store',$workpoint)
    //             ->where('P.code',$mod['code'])
    //             ->where('W.alias',$mod['alias'])
    //             ->update($cool);
    //             $mood[] = $mod['code'];
    //             }
    //             $res = count($mood);
        


    //             return response()->json($res);
    //         }else{return response()->json("No hay stock para replicar");}

    //     }else{return response()->json("No hay stock para replicar");}


    // }
    

}
