<?php

namespace App\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\Usuario;
use App\Mail\RecuperarSenha;
use App\Models\PasswordReset;

class RecuperarSenhaMutation
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
        $usuario = Usuario::where(['email' => $args['input']['email']])->first();
        $passwordReset = PasswordReset::firstOrNew([
            'email' => $usuario->email
        ]);
        $passwordReset->token = Str::random(60);
        $passwordReset->save();
        
        Mail::to($usuario->email)
            ->queue(new RecuperarSenha($usuario));

        return [
            'status' => 200,
            'message' =>  'Um link foi enviado para o seu email para que sua senha possa ser alterada'
        ];
    }
}
