<?php

namespace App\Http\Controllers;

use App\Models\Admin;



use Illuminate\Http\Request;

use App\Models\Resources\Users;
use App\Http\Requests\NewstaffRequest;
use Illuminate\Support\Facades\Redirect;
use App\Models\Blog;
use App\Models\Amici;
use App\Models\Resources\Richieste;
use App\Models\Resources\Post;
use App\Models\Resources\Messaggi;

use Carbon\Carbon;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AdminController extends Controller{

   protected $lista;   // sarebbe usermodel ma la chiamo lista perche idea è recuperare info
   protected $blogmodel;
   protected $postmodel;
   protected $richiestemodel;
   protected $amicimodel;

    public function __construct(){
        $this->middleware('can:isAdmin');
        $this->lista = new Users;// creo istanza di users per chiamre delle funzioni piu avanti per gestire staff
        $this->blogmodel = new Blog;
        $this->richiestemodel = new Richieste;
        $this->amicimodel = new Amici;
        $this->postmodel = new Post;
    }

//SEZIONE FUNZIONI LEGATE ALLE STATISTICHE


    public function index(){              // mi ritorna la view base di statistiche
         $users = $this->lista->getusers();
        return view('statistiche')
                ->with('users',$users);
    }



//  funzione che per un dato id dell'utente mi serve per avere una view dove vedo tutti i blogs creati da quell'utente
 public function showBlogsOfuser($id){
         $blogs = $this->blogmodel->getblogsofuser($id);
        return view('statistiche_blog')
                ->with('blogs',$blogs);
    }



// questa per un dato utente,che scelgo dal suo id , mi fa vedere dati sulle richieste a lui pervenute
 public function showRichiesteOfuser($id){
         $richieste = $this->richiestemodel->getrichiesteofuser($id);
        return view('statistiche_richieste')
                ->with('totali', $richieste[0])
                ->with('accettate', $richieste[1]);
    }



//questa per vedere gli amici di un dato utente identificato dal suo id
public function showAmiciOfuser($id){

         $amici = $this->lista->getamiciofuserLEFT($id);

         $amiciright=$this->lista->getamiciofuserRIGHT($id);

        return view('statistiche_amici')
                ->with('amici',$amici)->with('amiciright',$amiciright);
    }







//SEZIONE FUNZIONI LEGATE ALLA GESTIONE DELLO STAFF

    public function mettistaff(){
        return view('registrastaff');
    }



    public function storestaff(NewstaffRequest $request){      // mi serve per inserire un nuovo membro dello staff
        $staff= new Users;
        $staff->fill($request->validated());
      //  $staff->save();  //cosi va bene perche lo registra nel db, ma poi non cripta password e il login non ha successo

       $appoggio1=$staff['password'];        // devo utilizzare questo vecchio trucchetto per criptare la
        $appoggio2=Hash::make($appoggio1);    // password affinchè poi il login abbia successo
        $staff['password']=$appoggio2;        // per il nuovo membro staff che crea admin con questa funzione
        $staff->save();

        return redirect()->route('admin')
            ->with('status', 'Membro staff inserito correttamente!');
    }

// questa va bene
public function showstaff(){
        $staffs = $this->lista->getstaff();  // funzione definita in Users model e lanciata qui
        return view('gestionestaff')
                ->with('staffs',$staffs);
    }



public function deletestaff($id)
    {
        $staff = $this->lista->getThisstaff($id);
        $staff->delete();

        return  redirect()->route('gestiscistaff')
            ->with('status', 'membro staff eliminato correttamente!');
    }





    public function showStaffToUpdate($id){

        $staff = $this->lista->getThis($id);
        return view('modifica_staff')
                ->with('staff',$staff);

    }



    public function updatestaff(Request $request,$id)
    {
        $data = $request->validate([   // qui da mettere i validatori per i dati che inserisco nella form di modifica
            'name' => [ 'sometimes','string', 'max:255'],
            'cognome' => ['sometimes', 'string', 'max:255'],
            'sesso' => [ 'sometimes','string'],
            'data_nascita' => [ 'sometimes','date','before:18 years ago'],
            'email' => [ 'sometimes','string',  'regex:/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/'],
            'username' => ['sometimes', 'string', 'min:8'],  // qui alla fine ho tolto  -->  , 'unique:users' prima di ]

              'descrizione' => ['sometimes', 'string']
        ]);

        $staff = $this->lista->getThisstaff($id);
        $staff->update($data);

        return redirect()->route('gestiscistaff')
            ->with('status', 'Dati membro staff aggiornati correttamente!');
    }



//SEZIONE FUNZIONI LEGATE ALLA GESTIONE DEI BLOGS DEGLI UTENTI


//mi fa vedere la lista di tutti i blogs presenti
public function showallblogs(){

        $blogs = Blog::all();  // funzione definita in Users model e lanciata qui

        return view('gestioneblog_admin')
                ->with('blogs',$blogs);
    }


 public function showthisblog($id){ // id del blog

     $blog=Blog::where("id",$id)->first();

      $posts=Post::where ("blog",$id)->get();


     return view('vedi_questo_blog')
         ->with('blog',$blog)->with('posts',$posts);
     } //ok

  //elimina un post e mi riporta a vedere il blog aggiornato senza quel post
public function deletepost($idpost)
    {
        $post=Post:: where("id",$idpost)->first();
        $app=Post::where("id",$idpost)->value("blog");    // serve per passarlo alla rotta che mi porta a vedere il blog aggiornato dopo eliminazione

        $nomeblog=Blog::where("id",$app)->value("titolo");
        $app2=Blog::where("titolo",$nomeblog)->value("utente_proprietario");
        $app3=Users::where("id",$app2)->value("username");

        $app4=Post:: where("id",$idpost)->value("scrittore"); //è username di chi scrive il post
        $app5=Users::where("username",$app4)->value("id");

       $messaggio = new Messaggi([
            'contenuto' => "il tuo post : (".$post->contenuto.")è stato eliminato dal blog  ".$nomeblog."  di  ".$app3."  perchè non conforme alla nostra politica sui contenuti ",
            'data' => Carbon::now()->addHours(2),
            'mittente' => auth()->user()->id,
            'destinatario' => $app5

        ]);

        $messaggio->save();


     $post->delete();


        return redirect()->route('vedi_questo_blog_admin',$app)
        ->with('status', 'post eliminato correttamente correttamente!');
    }




public function deletethisblog($idblog)
    {
    $nomeblog=Blog::where("id",$idblog)->value("titolo");
     $blog=Blog::where("id",$idblog)->first();
      $app=Blog:: where("id",$idblog)->value("utente_proprietario");

     $messaggio = new Messaggi([
            'contenuto' => "il tuo blog ".$nomeblog." è stato eliminato perchè non conforme alla nostra politica sui contenuti ",
            'data' => Carbon::now()->addHours(2),
            'mittente' => auth()->user()->id,
            'destinatario' => $blog->utente_proprietario
        ]);
        $messaggio->save();

         $blog->delete();
        return  redirect()->route('listablogs')
            ->with('status', 'blog eliminato correttamente!');
    }





// questa chiude controller
}



