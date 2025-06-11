<?php

namespace App\Controller\API;

use App\DTO\LoanRequestDTO;
use App\Service\Data\LoanConstant;
use App\Service\LoanOfferService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @OA\Tag(name="Loan Offers")
 */
class LoanOfferController extends AbstractController
{
    /**
     * @OA\Post(
     *     path="/api/offres",
     *     summary="Rechercher des offres de prêt",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="amount", type="integer", example=50000),
     *             @OA\Property(property="duration", type="integer", example=15),
     *             @OA\Property(property="name", type="string", example="Jean Dupont"),
     *             @OA\Property(property="email", type="string", format="email", example="jonah@example.com"),
     *             @OA\Property(property="phone", type="string", example="+261332123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des offres triées",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="bank", type="string"),
     *                 @OA\Property(property="amount", type="integer"),
     *                 @OA\Property(property="duration", type="integer"),
     *                 @OA\Property(property="rate", type="number", format="float")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=204, description="Aucune offre disponible"),
     *     @OA\Response(response=400, description="Erreur de validation")
     * )
     */

    #[Route('/api/offers', name: 'api_offers', methods: ['POST'])]
    public function getOffers(
        Request $request,
        ValidatorInterface $validator,
        LoanOfferService $service
    ): JsonResponse {
        // Parse JSON content safely
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(
                ['errors' => [['field' => 'request', 'message' => 'Invalid JSON: ' . json_last_error_msg()]]],
                LoanConstant::STATUS_CODES['bad_request']
            );
        }

        // Ensure data is an array
        if (!is_array($data)) {
            return $this->json(
                ['errors' => [['field' => 'request', 'message' => 'Request body must be a JSON object']]],
                LoanConstant::STATUS_CODES['bad_request']
            );
        }

        // Populate DTO
        $dto = new LoanRequestDTO();
        $dto->amount = $data['amount'] ?? null;
        $dto->duration = $data['duration'] ?? null;
        $dto->name = $data['name'] ?? null;
        $dto->email = $data['email'] ?? null;
        $dto->phone = $data['phone'] ?? null;

        // Validate DTO
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $formatted = [];
            foreach ($errors as $error) {
                $formatted[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                ];
            }
            return $this->json(['errors' => $formatted], LoanConstant::STATUS_CODES['bad_request']);
        }

        // Get filtered offers
        $offers = $service->getFilteredOffers($dto->amount, $dto->duration);

        // Handle empty offers
        if (empty($offers)) {
            return $this->json(
                ['message' => 'Aucune offre disponible pour ce montant et cette durée.'],
                LoanConstant::STATUS_CODES['no_content']
            );
        }

        return $this->json($offers);
    }
}