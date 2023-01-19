<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $access = env("ACCESS_FILE");
        if(file_exists($access)){
        try{  $this->conn  = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
        
        // $schedule->call(function (){// replicador ventas de cedis jeje
        //     $workpoint = env('WORKPOINT');
        //     $date = now()->format("Y-m-d");
        //     if($workpoint == 1){
        //         $sday = DB::table('sales')->whereDate('created_at',$date)->where('_store',$workpoint)->get();
        //         if(count($sday) == 0){
        //             $sfsday = "SELECT TIPFAC&'-'&CODFAC AS TICKET, CLIFAC AS CLIENTE, CNOFAC AS NOMCLI, USUFAC AS USUARIO, ALMFAC AS ALMACEN, TOTFAC AS TOTAL, FOPFAC AS FORMAP, FORMAT(FECFAC,'YYYY-mm-dd')&' '&FORMAT(HORFAC,'HH:mm:ss') AS CREACION, '45' AS TERMINAL FROM F_FAC WHERE FECFAC =DATE() AND TIPFAC = '8' AND REFFAC  NOT LIKE '%CREDITO%' AND REFFAC NOT LIKE '%OCUPAR%'";
        //             $exec = $this->conn->prepare($sfsday);
        //             $exec -> execute();
        //             $fact=$exec->fetchall(\PDO::FETCH_ASSOC);
        //             if($fact){
        //                 foreach($fact as $fs){
        //                     $ptick[] = "'".$fs['TICKET']."'"; 
        //                     $client = DB::table('clients')->where('fs_id',$fs['CLIENTE'])->value('id');
        //                     $user = DB::table('users')->where('FS_id',$fs['USUARIO'])->value('id');
        //                     $warehouse = DB::table('warehouses')->where('alias',$fs['ALMACEN'])->where('_store',$workpoint)->value('id');
        //                     $payment =DB::table('payment_methods')->where('alias',$fs['FORMAP'])->value('id');
        //                     $cash = DB::table('cash_registers')->where('terminal',$fs['TERMINAL'])->value('id');
        //                     $facturas  = [
        //                         "num_ticket"=>$fs['TICKET'],
        //                         "_client"=>$client,
        //                         "name"=>$fs['NOMCLI'],
        //                         "_user"=>$user,
        //                         "_store"=>intval($workpoint),
        //                         "_warehouse"=>$warehouse,
        //                         "total"=>$fs['TOTAL'],
        //                         "_payment"=>$payment,
        //                         "created_at"=>$fs['CREACION'],
        //                         "updated_at"=>now(),//->toDateTimeString(),
        //                         "_cash"=>$cash
        //                     ];
        //                     $insert = DB::table('sales')->insert($facturas);        
        //                 }
        //                 $prday = "SELECT TIPLFA&'-'&CODLFA AS TICKET, ARTLFA AS ARTICULO, CANLFA AS CANTIDAD, PRELFA AS PRECIO, TOTLFA AS TOTAL, COSLFA AS COSTO FROM F_LFA WHERE TIPLFA&'-'&CODLFA IN (".implode(",",$ptick).")";
        //                 $exec = $this->conn->prepare($prday);
        //                 $exec -> execute();
        //                 $profac=$exec->fetchall(\PDO::FETCH_ASSOC);
        //                 foreach($profac as $pro){
        //                     $sale = DB::table('sales')->where('num_ticket',$pro['TICKET'])->where('_store',$workpoint)->value('id');
        //                     $product = DB::table('products')->where('code',$pro['ARTICULO'])->first();
        //                     $produ = [
        //                         "_sale"=>$sale,
        //                         "_product"=>$product->id,
        //                         "amount"=>$pro['CANTIDAD'],
        //                         "price"=>$pro['PRECIO'],
        //                         "total"=>$pro['TOTAL'],
        //                         "COST"=>$product->cost
        //                     ];
        //                     $insert = DB::table('sale_bodies')->insert($produ);
        //                 }
        //                 $paday = "SELECT TFALCO&'-'&CFALCO AS TICKET, FORMAT(FECLCO,'YYYY-mm-dd')&' '&'00:00:00' AS CREACION, IMPLCO AS TOTAL, CPTLCO AS  CONCEPTO,  IIF(F_CNP.DESCNP LIKE '%EFECTIVO%','EFECTIVO CEDIS', F_CNP.DESCNP) AS FAP, '45' AS TERMINAL FROM F_LCO LEFT JOIN F_CNP ON F_LCO.CPALCO  = F_CNP.CODCNP WHERE F_CNP.TIPCNP = 0 AND TFALCO&'-'&CFALCO IN (".implode(",",$ptick).")";
        //                 $exec = $this->conn->prepare($paday);
        //                 $exec -> execute();
        //                 $payfac=$exec->fetchall(\PDO::FETCH_ASSOC);
        //                 if($payfac){
        //                     $colsTab = array_keys($payfac[0]);//llaves de el arreglo 
        //                     foreach($payfac as $paym){
        //                         foreach($colsTab as $col){ $paym[$col] = utf8_encode($paym[$col]); }
        //                         $payme = DB::table('payment_methods AS PM')->join('counterparts AS C','C.id','PM._counterpart')->where('PM._type',5)->where('C.name',$paym['FAP'])->select('PM.id')->get();
        //                         $salep = DB::table('sales')->where('num_ticket',$paym['TICKET'])->where('_store',$workpoint)->value('id');
        //                         $cashs = DB::table('cash_registers')->where('terminal',$paym['TERMINAL'])->value('id');
        //                         foreach($payme as $pk){$res = $pk;}
        //                         $pagos = [
        //                             "_sale"=>$salep,
        //                             "created_at"=>$paym['CREACION'],
        //                             "total"=>$paym['TOTAL'],
        //                             "concept"=>$paym['CONCEPTO'],
        //                             "_collection"=>$res->id,
        //                             "_cash"=>$cashs
        //                         ];
        //                     }
        //                     $insert = DB::table('sale_collection_lines')->insert($pagos);
        //                 }
        //                 echo "Se añadieron ".count($fact)." facturas";
        //             }else{echo "No hay facturas que replicar bro";}    
        //         }else{
        //             foreach($sday as $sale){
        //                 $fact[]="'".$sale->num_ticket."'";
        //             }
        //             $sfsday = "SELECT TIPFAC&'-'&CODFAC AS TICKET, CLIFAC AS CLIENTE, CNOFAC AS NOMCLI, USUFAC AS USUARIO, ALMFAC AS ALMACEN, TOTFAC AS TOTAL, FOPFAC AS FORMAP, FORMAT(FECFAC,'YYYY-mm-dd')&' '&FORMAT(HORFAC,'HH:mm:ss') AS CREACION, '45' AS TERMINAL FROM F_FAC WHERE FECFAC =DATE() AND TIPFAC = '8' AND REFFAC  NOT LIKE '%CREDITO%'AND REFFAC NOT LIKE '%OCUPAR%' AND TIPFAC&'-'&CODFAC NOT IN (".implode(",",$fact).")";
        //             $exec = $this->conn->prepare($sfsday);
        //             $exec -> execute();
        //             $fact=$exec->fetchall(\PDO::FETCH_ASSOC);
        //             if($fact){
        //                 foreach($fact as $fs){
        //                     $ptick[] = "'".$fs['TICKET']."'";                    
        //                     $client = DB::table('clients')->where('fs_id',$fs['CLIENTE'])->value('id');
        //                     $user = DB::table('users')->where('FS_id',$fs['USUARIO'])->value('id');
        //                     $warehouse = DB::table('warehouses')->where('alias',$fs['ALMACEN'])->where('_store',$workpoint)->value('id');
        //                     $payment =DB::table('payment_methods')->where('alias',$fs['FORMAP'])->value('id');
        //                     $cash = DB::table('cash_registers')->where('terminal',$fs['TERMINAL'])->value('id');
        //                     $facturas  = [
        //                         "num_ticket"=>$fs['TICKET'],
        //                         "_client"=>$client,
        //                         "name"=>$fs['NOMCLI'],
        //                         "_user"=>$user,
        //                         "_store"=>intval($workpoint),
        //                         "_warehouse"=>$warehouse,
        //                         "total"=>$fs['TOTAL'],
        //                         "_payment"=>$payment,
        //                         "created_at"=>$fs['CREACION'],
        //                         "updated_at"=>now(),//->toDateTimeString(),
        //                         "_cash"=>$cash
        //                     ];
        //                     $insert = DB::table('sales')->insert($facturas);        
        //                 }
        //                 $prday = "SELECT TIPLFA&'-'&CODLFA AS TICKET, ARTLFA AS ARTICULO, CANLFA AS CANTIDAD, PRELFA AS PRECIO, TOTLFA AS TOTAL, COSLFA AS COSTO FROM F_LFA WHERE TIPLFA&'-'&CODLFA IN (".implode(",",$ptick).")";
        //                 $exec = $this->conn->prepare($prday);
        //                 $exec -> execute();
        //                 $profac=$exec->fetchall(\PDO::FETCH_ASSOC);
        //                 foreach($profac as $pro){
        //                     $sale = DB::table('sales')->where('num_ticket',$pro['TICKET'])->where('_store',$workpoint)->value('id');
        //                     $product = DB::table('products')->where('code',$pro['ARTICULO'])->first();
        //                     $produ = [
        //                         "_sale"=>$sale,
        //                         "_product"=>$product->id,
        //                         "amount"=>$pro['CANTIDAD'],
        //                         "price"=>$pro['PRECIO'],
        //                         "total"=>$pro['TOTAL'],
        //                         "COST"=>$product->cost
        //                     ];
        //                     $insert = DB::table('sale_bodies')->insert($produ);
        //                 }
        //                 $paday = "SELECT TFALCO&'-'&CFALCO AS TICKET, FORMAT(FECLCO,'YYYY-mm-dd')&' '&'00:00:00' AS CREACION, IMPLCO AS TOTAL, CPTLCO AS  CONCEPTO,  IIF(F_CNP.DESCNP LIKE '%EFECTIVO%','EFECTIVO CEDIS', F_CNP.DESCNP) AS FAP, '45' AS TERMINAL FROM F_LCO LEFT JOIN F_CNP ON F_LCO.CPALCO  = F_CNP.CODCNP WHERE F_CNP.TIPCNP = 0 AND TFALCO&'-'&CFALCO IN (".implode(",",$ptick).")";
        //                 $exec = $this->conn->prepare($paday);
        //                 $exec -> execute();
        //                 $payfac=$exec->fetchall(\PDO::FETCH_ASSOC);
        //                 if($payfac){
        //                     $colsTab = array_keys($payfac[0]);//llaves de el arreglo 
        //                     foreach($payfac as $paym){
        //                         foreach($colsTab as $col){ $paym[$col] = utf8_encode($paym[$col]); }
        //                         $salep = DB::table('sales')->where('num_ticket',$paym['TICKET'])->where('_store',$workpoint)->value('id');
        //                         $cashs = DB::table('cash_registers')->where('terminal',$paym['TERMINAL'])->value('id');
        //                         $payme = DB::table('payment_methods AS PM')->join('counterparts AS C','C.id','PM._counterpart')->where('PM._type',5)->where('C.name',$paym['FAP'])->select('PM.id')->get();
        //                         foreach($payme as $pk){$res = $pk;}
        //                         $pagos = [
        //                             "_sale"=>$salep,
        //                             "created_at"=>$paym['CREACION'],
        //                             "total"=>$paym['TOTAL'],
        //                             "concept"=>$paym['CONCEPTO'],
        //                             "_collection"=>$res->id,
        //                             "_cash"=>$cashs
        //                         ];
        //                     }
        //                     $insert = DB::table('sale_collection_lines')->insert($pagos);
        //                 }
        //                 echo "Se añadieron ".count($fact)." facturas";
        //             }else{echo "No hay facturas que replicar bro";}    
        //         }
        //     }
        // })->everyMinute()->between('8:00','22:00');

        $schedule->call(function (){//replicador de compras solo existira en cedis se ejecutara cada 30 min
            $date = now()->format('Y-m-d');
            $purshday = DB::table('purchases')->whereDate('created_at',$date)->get();
            $workpoint = env('WORKPOINT');
            if(count($purshday) == 0){
                $cfsday = "SELECT
                TIPFRE&'-'&CODFRE AS COMPRA,
                FACFRE AS FACTURA,
                PROFRE AS PROVIE,
                PNOFRE AS NOMPRO,
                ALMFRE AS ALMACEN,
                TOTFRE AS TOTAL,
                '13' AS PAGO,
                OB1FRE AS OBSERVA,
                IIF(HORFRE = '', FORMAT(FECFRE,'YYYY-mm-dd')&' '&'00:00:00', FORMAT(FENFRE,'YYYY-mm-dd')&' '&FORMAT(HORFRE,'HH:mm:ss')) AS FECHA_ENTREGA,
                FORMAT(FECFRE,'YYYY-mm-dd')&' '&'00:00:00' AS FECHA
                FROM F_FRE
                WHERE FECFRE = DATE()";
                $exec = $this->conn->prepare($cfsday);
                $exec -> execute();
                $fact=$exec->fetchall(\PDO::FETCH_ASSOC);
                if($fact){
                    $colsTab = array_keys($fact[0]);
                    foreach($fact as $reci){
                        foreach($colsTab as $col){ $reci[$col] = utf8_encode($reci[$col]); }
                        $provider = DB::table('providers')->where('fs_id',$reci['PROVIE'])->value('id');
                        $pcom[] = "'".$reci['COMPRA']."'"; 
                        $warehouse = DB::table('warehouses')->where('alias',$reci['ALMACEN'])->where('_store',$workpoint)->value('id');
                        $factu = [
                            "num_purchase"=>$reci['COMPRA'],
                            "num_invoice"=>$reci['FACTURA'],
                            "_provider"=>$provider,
                            "name"=>$reci['NOMPRO'],
                            "_warehouse"=>$warehouse,
                            "total"=>$reci['TOTAL'],
                            "_payment"=>$reci['PAGO'],
                            "observation"=>$reci['OBSERVA'],
                            "delivery_date"=>$reci['FECHA_ENTREGA'],
                            "created_at"=>$reci['FECHA'],
                            "updated_at"=>now(),//->toDateTimeString(),
                        ];
                        $insertms = DB::table('purchases')->insert($factu);
                    }
                    $cspr = "SELECT
                    TIPLFR&'-'&CODLFR AS COMPRA,
                    ARTLFR AS ARTICULO,
                    CANLFR AS CANTIDAD,
                    PRELFR AS PRECIO,
                    TOTLFR AS TOTAL
                    FROM F_LFR
                    WHERE TIPLFR&'-'&CODLFR IN (".implode(",",$pcom).")";
                    $exec = $this->conn->prepare($cspr);
                    $exec -> execute();
                    $pro=$exec->fetchall(\PDO::FETCH_ASSOC);
                    foreach($pro as $prms){
                        $product = DB::table('products')->where('code',$prms['ARTICULO'])->value('id');
                        $purcha = DB::table('purchases')->where('num_purchase',$prms['COMPRA'])->value('id');
                        $propu = [
                            "_purchase"=>$purcha,
                            "_product"=>$product,
                            "amount"=>$prms['CANTIDAD'],
                            "price"=>$prms['PRECIO'],
                            "total"=>$prms['TOTAL']
                        ];
                        $insertpr = DB::table('purchase_bodies')->insert($propu);
                    }
                    $paym = "SELECT 
                    TFRLPF&'-'&CFRLPF AS COMPRA,
                    IMPLPF AS IMPORTE,
                    CPTLPF AS CONCEPTO,
                    IIF(F_CNP.DESCNP LIKE '%EFECTIVO%','EFECTIVO CEDIS', F_CNP.DESCNP) AS PAGO,
                    FORMAT(FECLPF,'YYYY-mm-dd')&' '&'00:00:00' AS FECHA
                    FROM F_LPF
                    LEFT JOIN F_CNP ON F_LPF.CPALPF  = F_CNP.CODCNP
                    WHERE F_CNP.TIPCNP = 1 AND  TFRLPF&'-'&CFRLPF IN (".implode(",",$pcom).")";
                    $exec = $this->conn->prepare($paym);
                    $exec -> execute();
                    $paymen=$exec->fetchall(\PDO::FETCH_ASSOC);
                    if($paymen){
                        $colsTab = array_keys($paymen[0]);
                        foreach($paymen as $pago){
                            foreach($colsTab as $col){ $pago[$col] = utf8_encode($pago[$col]); }
                            $purpay = DB::table('purchases')->where('num_purchase',$pago['COMPRA'])->value('id');
                            $payme = DB::table('payment_methods AS PM')->join('counterparts AS C','C.id','PM._counterpart')->where('PM._type',5)->where('C.name',$pago['PAGO'])->select('PM.id')->get();
                            foreach($payme as $pk){$res = $pk;}

                            $paypur = [
                                "_purchase"=>$purpay,
                                "total"=>$pago['IMPORTE'],
                                "concept"=>$pago['CONCEPTO'],
                                "_payment"=>$res->id,
                                "created_at"=>$pago['FECHA']
                            ];
                            $insertpay =DB::table('purchase_payment_lines')->insert($paypur);
                        }
                        
                    }echo "Se insertaron ".count($fact)." facturas recibidas";
                }else{echo "No hay compras para replicar";}
            }else{
                foreach($purshday as $purchase){
                    $idsp[]="'".$purchase->num_purchase."'";
                }
                $cfsday = "SELECT
                TIPFRE&'-'&CODFRE AS COMPRA,
                FACFRE AS FACTURA,
                PROFRE AS PROVIE,
                PNOFRE AS NOMPRO,
                ALMFRE AS ALMACEN,
                TOTFRE AS TOTAL,
                '13' AS PAGO,
                OB1FRE AS OBSERVA,
                IIF(HORFRE = '', FORMAT(FECFRE,'YYYY-mm-dd')&' '&'00:00:00', FORMAT(FENFRE,'YYYY-mm-dd')&' '&FORMAT(HORFRE,'HH:mm:ss')) AS FECHA_ENTREGA,
                FORMAT(FECFRE,'YYYY-mm-dd')&' '&'00:00:00' AS FECHA
                FROM F_FRE
                WHERE FECFRE = DATE() AND TIPFRE&'-'&CODFRE NOT IN (".implode(",",$idsp).")";
                $exec = $this->conn->prepare($cfsday);
                $exec -> execute();
                $fact=$exec->fetchall(\PDO::FETCH_ASSOC);
                if($fact){
                    $colsTab = array_keys($fact[0]);
                    foreach($fact as $reci){
                        foreach($colsTab as $col){ $reci[$col] = utf8_encode($reci[$col]); }
                        $pcom[] = "'".$reci['COMPRA']."'"; 
                        $provider = DB::table('providers')->where('fs_id',$reci['PROVIE'])->value('id');
                        $warehouse = DB::table('warehouses')->where('alias',$reci['ALMACEN'])->where('_store',$workpoint)->value('id');
                        $factu = [
                            "num_purchase"=>$reci['COMPRA'],
                            "num_invoice"=>$reci['FACTURA'],
                            "_provider"=>$provider,
                            "name"=>$reci['NOMPRO'],
                            "_warehouse"=>$warehouse,
                            "total"=>$reci['TOTAL'],
                            "_payment"=>$reci['PAGO'],
                            "observation"=>$reci['OBSERVA'],
                            "delivery_date"=>$reci['FECHA_ENTREGA'],
                            "created_at"=>$reci['FECHA'],
                            "updated_at"=>now(),//->toDateTimeString(),
                        ];
                        $insertms = DB::table('purchases')->insert($factu);
                    }
                    $cspr = "SELECT
                    TIPLFR&'-'&CODLFR AS COMPRA,
                    ARTLFR AS ARTICULO,
                    CANLFR AS CANTIDAD,
                    PRELFR AS PRECIO,
                    TOTLFR AS TOTAL
                    FROM F_LFR
                    WHERE TIPLFR&'-'&CODLFR IN (".implode(",",$pcom).")";
                    $exec = $this->conn->prepare($cspr);
                    $exec -> execute();
                    $pro=$exec->fetchall(\PDO::FETCH_ASSOC);
                    foreach($pro as $prms){
                        $product = DB::table('products')->where('code',$prms['ARTICULO'])->value('id');
                        $purcha = DB::table('purchases')->where('num_purchase',$prms['COMPRA'])->value('id');
                        $propu = [
                            "_purchase"=>$purcha,
                            "_product"=>$product,
                            "amount"=>$prms['CANTIDAD'],
                            "price"=>$prms['PRECIO'],
                            "total"=>$prms['TOTAL']
                        ];
                        $insertpr = DB::table('purchase_bodies')->insert($propu);
                    }
                    $paym = "SELECT 
                    TFRLPF&'-'&CFRLPF AS COMPRA,
                    IMPLPF AS IMPORTE,
                    CPTLPF AS CONCEPTO,
                    IIF(F_CNP.DESCNP LIKE '%EFECTIVO%','EFECTIVO CEDIS', F_CNP.DESCNP) AS PAGO,
                    FORMAT(FECLPF,'YYYY-mm-dd')&' '&'00:00:00' AS FECHA
                    FROM F_LPF
                    LEFT JOIN F_CNP ON F_LPF.CPALPF  = F_CNP.CODCNP
                    WHERE F_CNP.TIPCNP = 1 AND  TFRLPF&'-'&CFRLPF IN (".implode(",",$pcom).")";
                    $exec = $this->conn->prepare($paym);
                    $exec -> execute();
                    $paymen=$exec->fetchall(\PDO::FETCH_ASSOC);
                    if($paymen){
                        $colsTab = array_keys($paymen[0]);
                        foreach($paymen as $pago){
                            foreach($colsTab as $col){ $pago[$col] = utf8_encode($pago[$col]); }
                            $payme = DB::table('payment_methods AS PM')->join('counterparts AS C','C.id','PM._counterpart')->where('PM._type',5)->where('C.name',$pago['PAGO'])->select('PM.id')->get();
                            $purpay = DB::table('purchases')->where('num_purchase',$pago['COMPRA'])->value('id');
                            foreach($payme as $pk){$res = $pk;}
                            $paypur = [
                                "_purchase"=>$purpay,
                                "total"=>$pago['IMPORTE'],
                                "concept"=>$pago['CONCEPTO'],
                                "_payment"=>$res->id,
                                "created_at"=>$pago['FECHA']
                            ];
                            $insertpay =DB::table('purchase_payment_lines')->insert($paypur);
                        }
                    }
                    echo "Se insertaron ".count($fact)." facturas recibidas";
                }else{echo "No hay compras para replicar";}
            }
        })->everyMinute()->between('8:00','22:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
