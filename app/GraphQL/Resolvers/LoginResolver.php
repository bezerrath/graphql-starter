<?php

namespace App\GraphQL\Resolvers;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class LoginResolver
{

    /**
     * @param $rootValue
     * @param array $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     * @param \GraphQL\Type\Definition\ResolveInfo $resolveInfo
     * @return array
     * @throws \Exception
     */
    public function resolve($rootValue, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo)
    {
        $credentials = [
            'email'=>$args['input']['email'],
            'password'=>$args['input']['password']
        ];
        
        if (! $token = auth()->attempt($credentials)) {
            return [
                'status'=>401,
                'message'=>'Login ou senha inválidos',
            ];
        }


        return [
            'usuario'=>auth()->user(),
            'status'=>200,
            'message'=>'OK',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }

}