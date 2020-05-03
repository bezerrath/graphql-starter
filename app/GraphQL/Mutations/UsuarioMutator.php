<?php

namespace App\GraphQL\Mutations;

use App\Mail\RecuperarSenha;
use App\Models\PasswordReset;
use App\Models\Usuario;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use App\Notifications\SenhaAlteradaNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UsuarioMutator
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
        // TODO implement the resolver
    }

    public function login($rootValue, array $args)
    {
        $credentials = [
            'email' => $args['input']['email'],
            'password' => $args['input']['password']
        ];

        if (!$token = auth()->attempt($credentials)) {
            return [
                'status' => 401,
                'message' => 'Login ou senha inválidos',
            ];
        }


        return [
            'usuario' => auth()->user(),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }

    public function logout($rootValue, array $args)
    {
        auth()->logout(true);
        return [
            'status' => 200,
            'message' => 'Sessão finalizada',
        ];
    }

    public function alteraPerfil($rootValue, array $args)
    {
        $usuario = Usuario::find($args['id']);

        if ($usuario->id !== auth("api")->user()->id) {
            return [
                'status' => 401,
                'message' => 'Não autorizado'
            ];
        }

        $usuario->fill($args['input']);
        $usuario->save();

        return [
            'status' => 200,
            'message' => 'OK',
            'perfil' => $usuario
        ];
    }

    public function alteraSenha($rootValue, array $args)
    {
        //Altera senha
        $usuario = auth("api")->user();
        if ($usuario === null) {
            return [
                'status' => 401,
                'message' => 'Não autorizado'
            ];
        }
        $usuario->password = Hash::make($args['password']);
        $usuario->save();

        //Registra notificação
        $usuario->notify(new SenhaAlteradaNotification());


        return [
            'status' => 200,
            'message' => 'Senha alterada'
        ];
    }

    public function novoUsuario($rootValue, array $args)
    {
        $usuario = Usuario::create($args);
        return $usuario;
    }

    public function recuperaSenha($rootValue, array $args)
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

    public function alteraSenhaComToken($rootValue, array $args)
    {
        //Verifica se o token existe
        $passwordReset = PasswordReset::where('token', $args['token'])->first();
        if ($passwordReset === null) {
            return [
                'status' => 404,
                'message' => 'Não encontrado'
            ];
        }

        //Verifica se o token válido
        if ($passwordReset->is_expirado) {
            return [
                'status' => 401,
                'message' => 'Token expirado'
            ];
        }

        //Altera senha
        $usuario = Usuario::where('email', $passwordReset->email)->first();
        if ($usuario === null) {
            return [
                'status' => 404,
                'message' => 'Email alterado'
            ];
        }
        $usuario->password = Hash::make($args['password']);
        $usuario->save();

        //Autentica Usuario
        $token = auth()->login($usuario);

        //Apaga PasswordReset
        $passwordReset->delete();

        //Registra notificação
        $usuario->notify(new SenhaAlteradaNotification());


        return [
            'status' => 200,
            'message' => 'Senha alterada',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }
}
