<?php

namespace App\Http\Controllers;

use App\Enums\StateEnum;
use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\ClientCollection;
use App\Http\Resources\ClientResource;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;
use App\Models\User;
use App\Traits\RestResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class ClientController extends Controller
{
    use RestResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //  return Client::whereNotNull('user_id')->get();
        $include = $request->has('include') ?  [$request->input('include')] : [];

        $data = Client::with($include)->whereNotNull('user_id')->get();
        //return  response()->json(['data' => $data]);
        //  return  ClientResource::collection($data);
        // return new ClientCollection($data);
        $clients = QueryBuilder::for(Client::class)
            ->allowedFilters(['surname'])
            ->allowedIncludes(['user'])
            ->get();
        return new ClientCollection($clients);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        try {
            DB::beginTransaction();
            $clientRequest =  $request->only('surname', 'adresse', 'telephone');
            /*   dd($clientRequest); */
            $client = Client::create($clientRequest);
            if ($request->has('user')) {
                $user = User::create([
                    'nom' => $request->input('user.nom'),
                    'prenom' => $request->input('user.prenom'),
                    'login' => $request->input('user.login'),
                    'password' => $request->input('user.password'),
                    'role_id' => $request->input('user.role'),  // Correction du champ
                    'etat' => true  // Par exemple, ajouter un état par défaut
                ]);

            }
            $user->client()->save($client);
            DB::commit();
            return $this->sendResponse(new ClientResource($client), StateEnum::SUCCESS, 'ghzfjzf');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendResponse(new ClientResource($e->getMessage()), StateEnum::ECHEC, 'error ', 500);
        }
    }

    //add method to display all users
    public function users()
    {
        return User::all();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }
    /**
     * Authentifie un utilisateur et génère un token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('login', $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Identifiants invalides',
                401
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $client = $user->client;

        return $this->sendResponse(
            [
                'user' => new ClientResource($client),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            \App\Enums\StateEnum::SUCCESS,
            'Connexion réussie',
            200
        );
    }
}
