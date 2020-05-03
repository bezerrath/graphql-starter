<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa mutation "login" com usuário válido
     *
     * @return void
     */
    public function test_login_com_credenciais_validas()
    {
        $this->cria_dados_validos();

        $response = $this->graphQL('
            mutation{
                login(input:{
                email:"operador@mail.com",
                password:"123456"
                }){
                usuario{
                    id
                }
                }
            }
        ')->assertJson([
            'data' => [
                'login' => [
                    'usuario' => [
                        'id' => '1'
                    ]
                ],
            ],

        ]);
    }

    /**
     * Testa mutation "logout" de um usuário autenticado
     * 
     * @return void
     */
    public function test_logout_com_usuario_autenticado()
    {
        $this->cria_dados_validos();
        $usuario = Usuario::find(1);
        auth()->attempt(['email' => $usuario->email, 'password' => '123456']);
        $this->graphQL('
            mutation{
                logout
              }
        ')
            ->assertStatus(200);
    }


    /**
     * Retorna lista de usuários cadastrados apenas para usuários autenticados
     * 
     * @return void
     */
    public function test_lista_usuarios_cadastrados()
    {
        $this->cria_dados_validos();
        auth()->attempt($this->credenciais());
        $response = $this->graphQL('
        {
            usuarios{
              data{
                name
              }
            }
          }
        ');
        $names = $response->json("data.usuarios.data.*.name");
        $this->assertSame(
            [
                'Operador',
            ],
            $names
        );
    }

    /**
     * Cadastra novo usuário
     * 
     * @return void
     */
    public function test_cadastra_novo_usuario()
    {
        $this->cria_dados_validos();
        auth()->attempt($this->credenciais());
        $response = $this->graphQL('
        mutation{
            novo_usuario(input: {
                name: "Operador 2",
                email: "operador2@mail.com",
                password: "123456",
                password_confirmation: "123456"
            }){
              name
            }
          }
        ')->assertJson([
            'data' => [
                'novo_usuario' => [
                    'name' => 'Operador 2'
                ]
            ]
        ]);
    }

    /**
     * Recuperar senha
     * 
     * @return void
     */
    public function test_recupera_senha_de_um_usuario()
    {
        $this->cria_dados_validos();
        auth()->attempt($this->credenciais());
        $response = $this->graphQL('
        mutation{
            recuperar_senha(input: {
                email: "operador@mail.com",
            })
          }
        ')->assertJson([
            'data' => [
                'recuperar_senha' => true
            ]
        ]);
    }

    /**
     * Altera senha com token
     * 
     * @return void
     */
    public function test_altera_senha_com_token_de_um_usuario_nao_autenticado()
    {
        $this->cria_dados_validos();
        auth()->attempt($this->credenciais());
        $response = $this->graphQL('
        mutation{
            recuperar_senha(input: {
                email: "operador@mail.com",
            })
          }
        ');
        auth()->logout();
        $token = (PasswordReset::find(1))->token;
        $response = $this->graphQL('
        mutation ($token: String!){
            altera_senha_com_token(input: {
                token: $token,
                password: "123456",
                password_confirmation: "123456"
            }){
                token_type
            }
          }
        ', [
            'token' => $token
        ])->assertJson([
            'data' => [
                'altera_senha_com_token' => [
                    'token_type' => 'bearer'
                ]
            ]
        ]);
    }

    /**
     * Retorna dados do perfil
     * 
     * @return void
     */
    public function test_retorna_dados_de_um_usuario_autenticado()
    {
        $this->cria_dados_validos();
        auth()->attempt($this->credenciais());

        $this->graphQL('
        {
            perfil{
                id
                name
                email
            }
        }
        ')->assertJson([
            'data' => [
                'perfil' => [
                    'id' => 1,
                    'name' => 'Operador',
                    'email' => 'operador@mail.com'
                ]
            ]
        ]);
    }

    /**
     * Altera senha de um usuário autenticado
     * 
     * @return void
     */
    public function test_altera_senha_de_um_usuario_autenticado()
    {
        $this->cria_dados_validos();
        auth()->attempt($this->credenciais());

        $this->graphQL('
            mutation{
                altera_senha( input: {
                    password: "123456",
                    password_confirmation: "123456"
                })
            }
        ')->assertJson([
            'data' => [
                'altera_senha' => true
            ]
        ]);
    }

    /**
     * Altera dados do perfil do usuario autenticado
     * 
     * @return void
     */
    public function test_altera_dados_do_perfil_de_um_usuario_autenticado()
    {
        $this->cria_dados_validos();
        auth()->attempt($this->credenciais());

        $this->graphQL('
            mutation{
                altera_perfil(id:1, input:{
                    name: "Operador Editado"
                }){
                    perfil{
                        name
                    }
                }
            }
        ')->assertJson([
            'data'=>[
                'altera_perfil'=>[
                    'perfil'=>[
                        'name'=>'Operador Editado'
                    ]
                ]
            ]
        ]);
    }

    /**
     * Cria dados válidos para serem usados nos testes de escopo de usuário
     * 
     * @return void
     */
    public function cria_dados_validos()
    {
        Usuario::create([
            'name' => 'Operador',
            'email' => 'operador@mail.com',
            'password' => Hash::make('123456')
        ]);
    }


    /**
     * Credenciais para usuário comum
     * 
     * @return array
     */
    public function credenciais()
    {
        $usuario = Usuario::find(1);
        return [
            'email' => $usuario->email,
            'password' => '123456'
        ];
    }
}
