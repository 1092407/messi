<?php

namespace App\Models\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Resources\Users;

class Richieste extends Model {     // moficiata, fare attenzione a accettante e richiedente su $fillable

    protected $table = 'richieste';
    protected $primaryKey = 'id';
    protected $fillable=['data_richiesta','data_risposta','stato','richiedente','accettante'];
    public $timestamps = false; #Ci consente di evitare che vengano aggiunte le due colonne per tracciare la data di inserimento equella di ultima modifica



// per un certo utente,che passo per id , voglio tutte le richieste e quelle che ha acettato

public function getrichiesteofuser($id){
 $totali=Richieste::where("accettante",$id)->count();  // mi conta TUTTE le richieste arrivate
$accettate=Richieste::where("accettante",$id)->where("stato","2")->count();   //mi conta solo  quelle accetate,cioè con stato settato=2. Scritto anche nella migration richieste che descrive la struttura dati
 return [$totali,$accettate];
    }

    //fin qui ok

   public function getRichiesta($id){
        $richiesta= Richieste::find($id);
        return $richiesta;
    }



//questa funzione permette all'utente loggato di vedere le richieste che puo accettatre o rifiutare

public function getAmiciziaRichieste(){
      $id=auth()->user()->id;

        $richieste_amicizia = Richieste::join('users','richieste.richiedente','=','users.id')
            ->where('richieste.accettante', $id)
            ->where('stato','=',1)   //cioè quelle in attesa
            ->select('richieste.id','richieste.data_richiesta','richieste.stato','richieste.richiedente','richieste.accettante','users.name','users.cognome','users.username','users.descrizione','users.foto_profilo')
            ->get();
        return $richieste_amicizia;
    }




// questa chiude l'estensione del model
}

