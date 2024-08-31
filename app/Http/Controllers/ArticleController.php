<?php

namespace App\Http\Controllers;
use Exception;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Http\Requests\UpdateStockRequest; // Assurez-vous d'avoir créé cette requête de validation
use App\Http\Requests\StoreArticleRequest; 
use Illuminate\Support\Facades\DB;
use App\Traits\RestResponseTrait;
 // Importez le trait


class ArticleController extends Controller
{
    use RestResponseTrait; // Utilisez le trait

    /**
     * Stocke un nouvel article dans la base de données.
     *
     * @param  \App\Http\Requests\StoreArticleRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreArticleRequest $request)
    {
        // Créer un nouvel article avec les données validées
        $article = Article::create([
            'libelle' => $request->input('libelle'),
            'prix' => $request->input('prix'),
            'quantite_de_stock' => $request->input('quantite_de_stock'),
        ]);

        // Utiliser le trait pour retourner une réponse JSON avec les détails de l'article créé
        return $this->sendResponse(
            $article,
            \App\Enums\StateEnum::SUCCESS,
            'Article créé avec succès !',
            201
        );
    }
    /**
     * Supprime un article de la base de données.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        // Rechercher l'article par ID
        $article = Article::find($id);

        // Vérifier si l'article existe
        if (!$article) {
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Article non trouvé',
                404
            );
        }

        // Supprimer l'article
        $article->delete();

        // Retourner une réponse de succès
        return $this->sendResponse(
            null,
            \App\Enums\StateEnum::SUCCESS,
            'Article supprimé avec succès !',
            200
        );
    }
    public function updateStock(UpdateStockRequest $request)
    {
        // Récupère les données validées à partir de la requête, spécifiquement les mises à jour de stock.
        $updates = $request->validated()['updates'];
    
        // Tableaux pour stocker les mises à jour réussies et échouées.
        $successfulUpdates = [];
        $failedUpdates = [];
    
        // Démarre une transaction de base de données pour s'assurer que toutes les opérations sont atomiques.
        DB::beginTransaction();
    
        try {
            // Parcourt chaque mise à jour de stock.
            foreach ($updates as $update) {
                try {
                     // Vérifie si la quantité saisie est inférieure à 0
                if ($update['quantite'] < 0) {
                    throw new Exception('La quantité ne peut pas être inférieure à 0.');
                }
                    // Tente de trouver l'article à mettre à jour par son ID.
                    $article = Article::findOrFail($update['id']);
                    
                    // Sauvegarde l'ancienne quantité de stock pour référence.
                    $oldQuantity = $article->quantite_de_stock;
                    
                    // Ajoute la quantité spécifiée au stock actuel.
                    $article->quantite_de_stock += $update['quantite'];
                    
                    // Sauvegarde l'article mis à jour dans la base de données.
                    $article->save();
    
                    // Ajoute les détails de la mise à jour réussie au tableau des mises à jour réussies.
                    $successfulUpdates[] = [
                        'id' => $article->id,
                        'old_quantity' => $oldQuantity,
                        'new_quantity' => $article->quantite_de_stock,
                        'added_quantity' => $update['quantite']
                    ];
                } catch (Exception $e) {
                    // Si une mise à jour échoue, ajoute les détails de l'échec au tableau des mises à jour échouées.
                    $failedUpdates[] = [
                        'article' => $update,
                        'error' => $e->getMessage()
                    ];
                }
            }
    
            // Si toutes les mises à jour sont réussies, on valide la transaction.
            DB::commit();
    
            // Détermine le statut global en fonction des résultats des mises à jour.
            $status = empty($failedUpdates) ? \App\Enums\StateEnum::SUCCESS : \App\Enums\StateEnum::ECHEC;
            
            // Prépare le message de réponse en fonction du succès ou de l'échec des mises à jour.
            $message = empty($failedUpdates) 
                ? 'Tous les stocks ont été mis à jour avec succès !'
                : 'Certaines mises à jour de stock ont échoué. Veuillez vérifier les détails.';
    
            // Retourne une réponse HTTP avec les détails des mises à jour réussies et échouées.
            return $this->sendResponse(
                [
                    'successful_updates' => $successfulUpdates,
                    'failed_updates' => $failedUpdates
                ],
                $status,
                $message,
                200
            );
        } catch (Exception $e) {
            // En cas d'erreur globale, annule toutes les modifications dans la transaction.
            DB::rollBack();
    
            // Retourne une réponse HTTP avec un message d'erreur.
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Erreur globale lors de la mise à jour des stocks : ' . $e->getMessage(),
                500
            );
        }
    }
    /**
 * Récupère un article par son ID.
 *
 * @param  int  $id
 * @return \Illuminate\Http\JsonResponse
 */
public function get($id)
{
    try {
        // Rechercher l'article par ID
        $article = Article::find($id);

        // Vérifier si l'article existe
        if (!$article) {
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Article non trouvé',
                404
            );
        }

        // Retourner l'article trouvé
        return $this->sendResponse(
            $article,
            \App\Enums\StateEnum::SUCCESS,
            'Article récupéré avec succès !',
            200
        );

    } catch (Exception $e) {
        // Retourner une réponse en cas d'erreur
        return $this->sendResponse(
            null,
            \App\Enums\StateEnum::ECHEC,
            'Erreur lors de la récupération de l\'article : ' . $e->getMessage(),
            500
        );
    }
}

    
}
