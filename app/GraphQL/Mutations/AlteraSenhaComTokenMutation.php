<?php

namespace App\GraphQL\Mutations;

use App\Models\PasswordReset;
use App\Models\Usuario;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Facades\Hash;

class AlteraSenhaComTokenMutation
{
    /**
     * Return a value for the field.
     *
     * @param  null  $rootValue Usually contains the result returned from the parent field. In this case, it is always `null`.
     * @param  mixed[]  $args The arguments that were passed into the field.
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context Arbitrary data that is shared between all fields of a single query.
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo Information about the query itself, such as the execution state, the field name, path to the field from the root, and more.
     * @return mixed
     */
    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        //Verifica se o token existe
        $passwordReset = PasswordReset::where('token', $args['token'])->first();
        if($passwordReset === null){
            return [
                'status'=>404,
                'message'=>'Não encontrado'
            ];
        }

        //Verifica se o token válido
        if($passwordReset->is_expirado){
            return [
                'status'=>401,
                'message'=>'Token expirado'
            ];
        }

        //Altera senha
        $usuario = Usuario::where('email', $passwordReset->email)->first();
        if($usuario === null){
            return [
                'status'=>404,
                'message'=>'Email alterado'
            ];
        }
        $usuario->password = Hash::make($args['input']['password']);
        $usuario->save();

        //Autentica Usuario
        $token = auth()->login($usuario);

        //Apaga PasswordReset
        $passwordReset->delete();


        return [
            'status'=>200,
            'message'=>'Senha alterada',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }
}
