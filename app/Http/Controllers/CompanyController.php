<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *      title="My Laravel API",
 *      version="1.0.0",
 *      description="API Documentation for my Laravel application",
 *      @OA\Contact(
 *          email="support@example.com"
 *      ),
 *      @OA\License(
 *          name="Apache 2.0",
 *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *      )
 * )
 *
 * @OA\Tag(
 *     name="Companies",
 *     description="API Endpoints for Managing Companies"
 * )
 *
 * @OA\Schema(
 *     schema="Company",
 *     type="object",
 *     required={"id", "inn", "title", "user_id"},
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         description="Company ID"
 *     ),
 *     @OA\Property(
 *         property="inn",
 *         type="string",
 *         description="INN of the company"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="Title of the company"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         description="ID of the user who owns the company"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Creation timestamp"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Update timestamp"
 *     )
 * )
 */
class CompanyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/companies",
     *     summary="Get the list of companies for the authenticated user",
     *     description="Returns a list of companies that belong to the currently authenticated user",
     *     operationId="getUserCompanies",
     *     tags={"Companies"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Company"))
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function index(): JsonResponse
    {
        $companies = Company::query()->where('user_id', Auth::id())->get();
        return response()->json($companies);
    }

    /**
     * @OA\Post(
     *     path="/api/companies",
     *     summary="Create a new company",
     *     description="Creates a new company associated with the authenticated user. Restores a soft-deleted company if the same INN exists.",
     *     operationId="createCompany",
     *     tags={"Companies"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"inn", "title"},
     *             @OA\Property(property="inn", type="string", example="123456789012", description="INN of the company"),
     *             @OA\Property(property="title", type="string", example="My Company", description="Title of the company")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Company created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Company")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Company with this INN already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Company with this INN already exists")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'inn' => 'required|size:12',
            'title' => 'required|string|max:255',
        ]);

        $existingCompany = Company::withTrashed()->where('inn', $request->inn)->first();

        if ($existingCompany) {
            if ($existingCompany->trashed()) {
                $existingCompany->restore();
                $existingCompany->update([
                    'title' => $request->title,
                ]);

                return response()->json($existingCompany, 201);
            }

            return response()->json(['error' => 'Company with this INN already exists'], 409);
        }

        $company = new Company([
            'inn' => $request->inn,
            'title' => $request->title,
            'user_id' => Auth::id(),
        ]);

        $company->save();

        return response()->json($company, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/companies/{company}",
     *     summary="Update a company",
     *     description="Updates the title of a company. Only the owner of the company (authenticated user) can update it.",
     *     operationId="updateCompany",
     *     tags={"Companies"},
     *     @OA\Parameter(
     *         name="company",
     *         in="path",
     *         description="ID of the company to update",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title"},
     *             @OA\Property(property="title", type="string", example="Updated Company Title", description="Updated title of the company")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Company")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function update(Request $request, Company $company): JsonResponse
    {
        if ($company->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $company->update($request->only('title'));

        return response()->json($company);
    }

    /**
     * @OA\Delete(
     *     path="/api/companies/{company}",
     *     summary="Delete a company",
     *     description="Soft deletes a company. Only the owner of the company (authenticated user) can delete it.",
     *     operationId="deleteCompany",
     *     tags={"Companies"},
     *     @OA\Parameter(
     *         name="company",
     *         in="path",
     *         description="ID of the company to delete",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Company deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function destroy(Company $company): JsonResponse
    {
        if ($company->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Soft delete the company
        $company->delete();

        return response()->json(null, 204);
    }
}


