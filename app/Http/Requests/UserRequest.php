<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request)
    {
        $valid = validate_mobile($request->contact_number);
        $request->request->remove('contact_number');
        $request->request->add(['contact_number' => $valid]);
        
        $call_forwarding_number = validate_mobile($request->call_forwarding_number);
        $request->request->remove('call_forwarding_number');
        $request->request->add(['call_forwarding_number' => $call_forwarding_number]);

        $user_id = auth()->user()->id ?? request()->id;

        $rules = [
            // 'username'  => 'required|unique:users,username,'.$user_id,
            'email'     => "required|email|unique:App\Models\User,email,$user_id,id,deleted_at,NULL",
            'contact_number' => "max:20|unique:users,contact_number,$user_id,id,deleted_at,NULL",
            // 'call_forwarding_number' => "max:20|unique:users,call_forwarding_number,$user_id,id,deleted_at,NULL",
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'userProfile.dob.*'  => 'DOB is required.',
            'contact_number.unique' => 'Twilio Number Already been taken',
        ];
    }

    /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => false,
            'message' => $validator->errors()->first(),
            'all_message' =>  $validator->errors()
        ];

        if (request()->is('api*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json($data, 422));
        } else {
            throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
        }
    }
}
