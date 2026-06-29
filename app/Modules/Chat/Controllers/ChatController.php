<?php

declare(strict_types=1);

namespace App\Modules\Chat\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\PaidMessage;
use App\Models\User;
use App\Modules\Chat\DTOs\SendMessageDTO;
use App\Modules\Chat\Requests\SendMessageRequest;
use App\Modules\Chat\Services\MessageRefundService;
use App\Modules\Chat\Services\MessageService;
use Illuminate\Http\JsonResponse;

class ChatController extends BaseApiController
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly MessageRefundService $refundService
    ) {}

    public function getToken(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $token = $this->messageService->generateToken($user);

        $userData = [
            'id' => (string) $user->id,
            'name' => $user->creatorProfile ? $user->creatorProfile->display_name : $user->username,
            'image' => $user->avatar ? $user->avatar->url : null,
        ];

        return response()->json([
            'success' => true,
            'token' => $token,
            'apiKey' => config('stream.api_key'),
            'user' => $userData,
        ]);
    }

    public function send(SendMessageRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $paidMessage = $this->messageService->send(new SendMessageDTO(
            senderId: $user->id,
            receiverId: (int) $request->validated('receiver_id'),
            content: $request->validated('content'),
        ));

        return ApiResponse::created(
            data: $paidMessage->toArray(),
            message: 'Message sent successfully.'
        );
    }

    public function refund(int $id): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $paidMessage = PaidMessage::where('sender_id', $user->id)->findOrFail($id);

        $refund = $this->refundService->processRefund($paidMessage->id);

        return ApiResponse::success(
            data: $refund->toArray(),
            message: 'Refund processed successfully.'
        );
    }
}
