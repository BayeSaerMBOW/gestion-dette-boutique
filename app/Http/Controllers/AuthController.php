<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Passport\TokenRepository;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Récupère les informations d'identification (login et password) du requête
        $credentials = $request->only('login', 'password');
    
        // Essaye de connecter l'utilisateur avec les informations d'identification fournies
        if (Auth::attempt($credentials)) {
            // Si l'authentification réussit, récupère l'utilisateur actuellement authentifié
            $user = User::find(Auth::user()->id);
    
            // Crée un token d'accès pour l'utilisateur
            $token = $user->createToken('appToken')->accessToken;
    
            // Génère un refresh token pour l'utilisateur
            $refreshToken = $this->createRefreshToken($user);
    
            // Retourne une réponse JSON indiquant le succès de l'authentification
            // Inclut le token d'accès, le refresh token et les informations de l'utilisateur dans la réponse
            return response()->json([
                'success' => true,
                'token' => $token, // Token d'accès
                'refresh_token' => $refreshToken, // Refresh token
                'user' => $user, // Informations sur l'utilisateur
            ], 200);
        } else {
            // Si l'authentification échoue, retourne une réponse JSON indiquant l'échec
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate.', // Message d'erreur
            ], 401);
        }
    }
    
    protected function createRefreshToken($user)
    {
        // Obtient le TokenRepository à partir du service container
        $tokenRepository = app(TokenRepository::class);
    
        // Crée un token de rafraîchissement pour l'utilisateur
        $token = $user->createToken('refreshToken')->accessToken;
    
        // Sauvegarder le refresh token si nécessaire (commenté ici car non utilisé)
        // $tokenRepository->save($token);
    
        // Retourne le refresh token
        return $token;
    }
    
    
}
