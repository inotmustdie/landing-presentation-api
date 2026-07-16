<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __construct(private readonly ContactService $contactService)
    {
    }

    public function store(ContactRequest $request): JsonResponse
    {
        $result = $this->contactService->submit($request->validated(), $request->ip(), $request->userAgent());

        return response()->json([
            'message' => 'Contact request accepted.',
            'data' => $result,
        ], 201);
    }
}
