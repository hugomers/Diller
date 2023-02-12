<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchasesController extends Controller
{
    public function __construct(){
        $access = env("ACCESS_FILE");//conexion a access de sucursal
        if(file_exists($access)){
        try{  $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
    }

    public function requestSupplier(Request $request){

        $id = $request->id;
        $pedido = DB::table('purchases as P')->join('warehouses as W','W.id','P._warehouse')->join('users as U','U.id','P._user')->where('P.id',$id)->select('W.alias AS almacen','U.FS_id AS usuario','P.*')->first();
        if($pedido){
            if($pedido->_pending_purchase == null){
                if($pedido->_state == 2){
                    $type = null;
                    if($pedido->_make == 1){
                        $type = 3;
                    }else{
                        $type = 4;
                    }
                    $resuply = "SELECT MAX(CODPPR) as codigo FROM F_PPR WHERE TIPPPR = "."'".$type."'";
                    $exec = $this->conn->prepare($resuply);
                    $exec -> execute();
                    $idmax=$exec->fetch(\PDO::FETCH_ASSOC);              
                    $siguiente = $idmax['codigo'] + 1;
                    $insheader = [
                        $type,
                        $siguiente,
                        $pedido->reference,
                        now()->format('Y-m-d'),
                        $pedido->_provider,
                        $pedido->name,
                        $pedido->almacen,
                        $pedido->total,
                        $pedido->total,
                        $pedido->total,
                        $pedido->observation,
                        $pedido->usuario,
                        $pedido->usuario,
                        now()->format('Y-m-d')
                    ];
                    $insresuply = "INSERT INTO F_PPR (TIPPPR,CODPPR,REFPPR,FECPPR,PROPPR,PNOPPR,ALMPPR,NET1PPR,BAS1PPR,TOTPPR,OB1PPR,USUPPR,USMPPR,FUMPPR) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    $exec = $this->conn->prepare($insresuply);
                    $exec -> execute($insheader);
                    $folio = $type."-".str_pad($siguiente, 6, "0", STR_PAD_LEFT);
                    $upmshead = DB::table('purchases')->where('id',$id)->update(['id_fs_request'=>$folio]);

                    $bodies = DB::table('purchase_bodies AS PB')->join('products AS P','P.id','PB._product')->where('_purchase',$id)->select('P.code as codigo','P.description as descripcion','PB.*')->get();
                    $pos = 1;
                    foreach($bodies as $bodie){
                        $arti [] = $bodie->codigo;
                        $insbodie = [
                            $type,
                            $siguiente,
                            $pos,
                            $bodie->codigo,
                            $bodie->descripcion,
                            $bodie->requested_amount,
                            $bodie->price,
                            $bodie->total,
                            $bodie->requested_amount
                        ];
                        $pos++;
                        $insbo = "INSERT INTO F_LPP (TIPLPP,CODLPP,POSLPP,ARTLPP,DESLPP,CANLPP,PRELPP,TOTLPP,PENLPP) VALUES (?,?,?,?,?,?,?,?,?)";
                        $exec = $this->conn->prepare($insbo);
                        $exec -> execute($insbodie);
                    }
                    $res = [
                        "folioPedido"=>$folio,
                        "articulosPedido"=>count($arti)
                    ];
                    return response($res);
                }else if($pedido->_state == 1){
                    return response()->json("El pedido a proveedor aun no finaliza",400);
                }else if($pedido->_state > 2){
                    return response()->json("El pedido a proveedor ya esta en proceso de factura recibida",400);
                }
            }else{return response()->json("El pedido a proveedor ya existe",401);}
        }else{return response()->json("El pedido a proveedor no se encuentra",404);}

    }

    public function invoiceReceived(Request $request){
        $id = $request->id;
        $purchase = DB::table('purchases')->where('id',$id)->first();
        if($purchase){
            if($purchase->if_fs_invoice == null){
                



            }else{
                if($purchase->_pending_purchase = null){

                }else{
    
                }
            }

        }else{return response()->json("No se encuentra la compra",404);}
    }
}
