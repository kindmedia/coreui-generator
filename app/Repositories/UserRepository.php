<?php

namespace App\Repositories;

use App\Exceptions\ApiOperationFailedException;
use App\Models\User;
use App\Traits\ImageTrait;
use Auth;
use DB;
use Exception;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class UserRepository
 * @package App\Repositories
 * @version February 20, 2020, 7:15 am UTC
*/

class UserRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'email',
        'phone'
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return User::class;
    }

    /**
     * @param  array  $input
     *
     * @throws Exception
     *
     * @return bool
     */
    public function store($input)
    {
        $input = $this->validateInput($input);

        try {
            if (isset($input['photo']) && ! empty($input['photo'])) {
                $input['image_path'] = ImageTrait::makeImage($input['photo'], User::IMAGE_PATH);
            }

            User::create($input);

        } catch (Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return true;
    }

    /**
     * @param  int  $id
     * @param  array  $input
     *
     * @throws Exception
     * @throws ApiOperationFailedException
     * @return User
     */
    public function update($id, $input)
    {
        $input = $this->validateInput($input);
        /** @var User $user */
        $user = $this->findOrFail($id);
        try {
            DB::beginTransaction();
            if (isset($input['photo']) && ! empty($input['photo'])) {
                $user->deleteImage();
                $input['image_path'] = ImageTrait::makeImage($input['photo'], User::IMAGE_PATH);
            }

            $user->update($input);


            DB::commit();

            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApiOperationFailedException($e->getMessage());
        }
    }

    /**
     * @param  array  $input
     *
     * @return mixed
     */
    public function validateInput($input)
    {
        if (! empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            unset($input['password']);
        }

        return $input;
    }

    public function profileUpdate($input)
    {
        /** @var User $user */
        $user = $this->findOrFail(Auth::id());

        try {
            if (isset($input['photo']) && ! empty($input['photo'])) {
                $input['image_path'] = ImageTrait::makeImage(
                    $input['photo'], User::IMAGE_PATH, ['width' => 150, 'height' => 150]
                );

                $imagePath = $user->image_path;
                if (! empty($imagePath)) {
                    $user->deleteImage();
                }
            }

            $user->update($input);
        } catch (Exception $e) {
            if (! empty($input['image_path'])) {
                unlink(User::IMAGE_PATH.DIRECTORY_SEPARATOR.$input['image_url']);
            }
        }

        return true;
    }

}
