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
        
        $schedule->call(function (){
            $workpoint = env('WORKPOINT');
            $date = now()->format("Y-m-d");
            if($workpoint == 1){
                $sday = DB::table('sales')->whereDate('created_at',$date)->where('_store',$workpoint)->get();
                if(count($sday) == 0){
                    $sfsday = "SELECT TIPFAC&'-'&CODFAC AS TICKET, CLIFAC AS CLIENTE, CNOFAC AS NOMCLI, USUFAC AS USUARIO, ALMFAC AS ALMACEN, TOTFAC AS TOTAL, FOPFAC AS FORMAP, FORMAT(FECFAC,'YYYY-mm-dd')&' '&FORMAT(HORFAC,'HH:mm:ss') AS CREACION, '45' AS TERMINAL FROM F_FAC WHERE FECFAC =DATE() AND TIPFAC = '8' AND REFFAC  NOT LIKE '%CREDITO%'";
                    $exec = $this->conn->prepare($sfsday);
                    $exec -> execute();
                    $fact=$exec->fetchall(\PDO::FETCH_ASSOC);
                    if($fact){
                        foreach($fact as $fs){
                            $ptick[] = "'".$fs['TICKET']."'"; 
                            $client = DB::table('clients')->where('fs_id',$fs['CLIENTE'])->value('id');
                            $user = DB::table('users')->where('FS_id',$fs['USUARIO'])->value('id');
                            $warehouse = DB::table('warehouses')->where('alias',$fs['ALMACEN'])->where('_store',$workpoint)->value('id');
                            $payment =DB::table('payment_methods')->where('alias',$fs['FORMAP'])->value('id');
                            $cash = DB::table('cash_registers')->where('terminal',$fs['TERMINAL'])->value('id');
                            $facturas  = [
                                "num_ticket"=>$fs['TICKET'],
                                "_client"=>$client,
                                "name"=>$fs['NOMCLI'],
                                "_user"=>$user,
                                "_store"=>intval($workpoint),
                                "_warehouse"=>$warehouse,
                                "total"=>$fs['TOTAL'],
                                "_payment"=>$payment,
                                "created_at"=>$fs['CREACION'],
                                "updated_at"=>now(),//->toDateTimeString(),
                                "_cash"=>$cash
                            ];
                            $insert = DB::table('sales')->insert($facturas);        
                        }
                        $prday = "SELECT TIPLFA&'-'&CODLFA AS TICKET, ARTLFA AS ARTICULO, CANLFA AS CANTIDAD, PRELFA AS PRECIO, TOTLFA AS TOTAL, COSLFA AS COSTO FROM F_LFA WHERE TIPLFA&'-'&CODLFA IN (".implode(",",$ptick).")";
                        $exec = $this->conn->prepare($prday);
                        $exec -> execute();
                        $profac=$exec->fetchall(\PDO::FETCH_ASSOC);
                        foreach($profac as $pro){
                            $sale = DB::table('sales')->where('num_ticket',$pro['TICKET'])->where('_store',$workpoint)->value('id');
                            $product = DB::table('products')->where('code',$pro['ARTICULO'])->first();
                            $produ = [
                                "_sale"=>$sale,
                                "_product"=>$product->id,
                                "amount"=>$pro['CANTIDAD'],
                                "price"=>$pro['PRECIO'],
                                "total"=>$pro['TOTAL'],
                                "COST"=>$product->cost
                            ];
                            $insert = DB::table('sale_bodies')->insert($produ);
                        }
                        $paday = "SELECT TFALCO&'-'&CFALCO AS TICKET, FORMAT(FECLCO,'YYYY-mm-dd')&' '&'00:00:00' AS CREACION, IMPLCO AS TOTAL,CPTLCO AS  CONCEPTO, '13' AS FAP, '45' AS TERMINAL FROM F_LCO WHERE TFALCO&'-'&CFALCO IN (".implode(",",$ptick).")";
                        $exec = $this->conn->prepare($paday);
                        $exec -> execute();
                        $payfac=$exec->fetchall(\PDO::FETCH_ASSOC);
                        if($payfac){
                            $colsTab = array_keys($payfac[0]);//llaves de el arreglo 
                            foreach($payfac as $paym){
                                foreach($colsTab as $col){ $paym[$col] = utf8_encode($paym[$col]); }
                                $salep = DB::table('sales')->where('num_ticket',$paym['TICKET'])->where('_store',$workpoint)->value('id');
                                $cashs = DB::table('cash_registers')->where('terminal',$paym['TERMINAL'])->value('id');
                                $pagos = [
                                    "_sale"=>$salep,
                                    "created_at"=>$paym['CREACION'],
                                    "total"=>$paym['TOTAL'],
                                    "concept"=>$paym['CONCEPTO'],
                                    "_collection"=>$paym['FAP'],
                                    "_cash"=>$cashs
                                ];
                            }
                            $insert = DB::table('sale_collection_lines')->insert($pagos);
                        }
                    }else{echo "No hay facturas que replicar bro";}    
                }else{
                    foreach($sday as $sale){
                        $fact[]="'".$sale->num_ticket."'";
                    }
                    $sfsday = "SELECT TIPFAC&'-'&CODFAC AS TICKET, CLIFAC AS CLIENTE, CNOFAC AS NOMCLI, USUFAC AS USUARIO, ALMFAC AS ALMACEN, TOTFAC AS TOTAL, FOPFAC AS FORMAP, FORMAT(FECFAC,'YYYY-mm-dd')&' '&FORMAT(HORFAC,'HH:mm:ss') AS CREACION, '45' AS TERMINAL FROM F_FAC WHERE FECFAC =DATE() AND TIPFAC = '8' AND REFFAC  NOT LIKE '%CREDITO%' AND TIPFAC&'-'&CODFAC NOT IN (".implode(",",$fact).")";
                    $exec = $this->conn->prepare($sfsday);
                    $exec -> execute();
                    $fact=$exec->fetchall(\PDO::FETCH_ASSOC);
                    if($fact){
                        foreach($fact as $fs){
                            $ptick[] = "'".$fs['TICKET']."'";                    
                            $client = DB::table('clients')->where('fs_id',$fs['CLIENTE'])->value('id');
                            $user = DB::table('users')->where('FS_id',$fs['USUARIO'])->value('id');
                            $warehouse = DB::table('warehouses')->where('alias',$fs['ALMACEN'])->where('_store',$workpoint)->value('id');
                            $payment =DB::table('payment_methods')->where('alias',$fs['FORMAP'])->value('id');
                            $cash = DB::table('cash_registers')->where('terminal',$fs['TERMINAL'])->value('id');
                            $facturas  = [
                                "num_ticket"=>$fs['TICKET'],
                                "_client"=>$client,
                                "name"=>$fs['NOMCLI'],
                                "_user"=>$user,
                                "_store"=>intval($workpoint),
                                "_warehouse"=>$warehouse,
                                "total"=>$fs['TOTAL'],
                                "_payment"=>$payment,
                                "created_at"=>$fs['CREACION'],
                                "updated_at"=>now(),//->toDateTimeString(),
                                "_cash"=>$cash
                            ];
                            $insert = DB::table('sales')->insert($facturas);        
                        }
                        $prday = "SELECT TIPLFA&'-'&CODLFA AS TICKET, ARTLFA AS ARTICULO, CANLFA AS CANTIDAD, PRELFA AS PRECIO, TOTLFA AS TOTAL, COSLFA AS COSTO FROM F_LFA WHERE TIPLFA&'-'&CODLFA IN (".implode(",",$ptick).")";
                        $exec = $this->conn->prepare($prday);
                        $exec -> execute();
                        $profac=$exec->fetchall(\PDO::FETCH_ASSOC);
                        foreach($profac as $pro){
                            $sale = DB::table('sales')->where('num_ticket',$pro['TICKET'])->where('_store',$workpoint)->value('id');
                            $product = DB::table('products')->where('code',$pro['ARTICULO'])->first();
                            $produ = [
                                "_sale"=>$sale,
                                "_product"=>$product->id,
                                "amount"=>$pro['CANTIDAD'],
                                "price"=>$pro['PRECIO'],
                                "total"=>$pro['TOTAL'],
                                "COST"=>$product->cost
                            ];
                            $insert = DB::table('sale_bodies')->insert($produ);
                        }
                        $paday = "SELECT TFALCO&'-'&CFALCO AS TICKET, FORMAT(FECLCO,'YYYY-mm-dd')&' '&'00:00:00' AS CREACION, IMPLCO AS TOTAL, CPTLCO AS  CONCEPTO, '13' AS FAP, '45' AS TERMINAL FROM F_LCO WHERE TFALCO&'-'&CFALCO IN (".implode(",",$ptick).")";
                        $exec = $this->conn->prepare($paday);
                        $exec -> execute();
                        $payfac=$exec->fetchall(\PDO::FETCH_ASSOC);
                        if($payfac){
                            $colsTab = array_keys($payfac[0]);//llaves de el arreglo 
                            foreach($payfac as $paym){
                                foreach($colsTab as $col){ $paym[$col] = utf8_encode($paym[$col]); }
                                $salep = DB::table('sales')->where('num_ticket',$paym['TICKET'])->where('_store',$workpoint)->value('id');
                                $cashs = DB::table('cash_registers')->where('terminal',$paym['TERMINAL'])->value('id');
                                $pagos = [
                                    "_sale"=>$salep,
                                    "created_at"=>$paym['CREACION'],
                                    "total"=>$paym['TOTAL'],
                                    "concept"=>$paym['CONCEPTO'],
                                    "_collection"=>$paym['FAP'],
                                    "_cash"=>$cashs
                                ];
                            }
                            $insert = DB::table('sale_collection_lines')->insert($pagos);
                        }
                    }else{echo "No hay facturas que replicar bro";}    
                }
            }
        })->everyMinute();
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
