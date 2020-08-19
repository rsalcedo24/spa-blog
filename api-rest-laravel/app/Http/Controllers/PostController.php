<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class postController extends Controller {

    public function __construct() {
        $this->middleware('api.auth', ['except' => [
                'index',
                'show',
                'getImage',
                'getPostsByCategory',
                'getPostsByUser'
        ]]);
    }

    public function index() {
        $posts = Post::all()->load('category');
                            

        return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

    public function show($id) {
        $post = Post::find($id)->load('category')
                                ->load('user');

        if (is_object($post)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'posts' => $post
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La entrada no existe.'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request) {
        //RECOGER DATOS POR POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //CONSEGUIR USUARIO IDENTIFICADO
            $user = $this->getIdentity($request);

            //VALIDAR DATOS
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'content' => 'required',
                        'category_id' => 'required',
                        'image' => 'required'
            ]);

            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado el post, faltan datos.'
                ];
            } else {
                //GUARDAR POST
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                ];
            }
        } else {
            $data = [
                'code' => 400,
                'status' => 'success',
                'message' => 'Los datos han sido enviados correctamente.'
            ];
        }

        //DEVOLVER RESPUESTA
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request) {
        //RECOGER DATOS POR POST
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        //DATOS PARA DEVOLVER
        $data = array(
            'code' => 400,
            'status' => 'error',
            'message' => 'Datos enviados incorrectamente.'
        );

        if (!empty($params_array)) {
            //VALIDAR LOS DATOS
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'content' => 'required',
                        'category_id' => 'required'
            ]);

            if ($validate->fails()) {
                $data['errors'] = $validate->errors();
                return response()->json($data, $data['code']);
            }
            //ELIMINAR LO QUE NO ACTUALIZAREMOS
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['user']);

            //CONSEGUIR USUARIO IDENTIFICADO
            $user = $this->getIdentity($request);

            //BUSCAR EL REGISTRO A ACTUALIZAR
            $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();


            if (!empty($post) && is_object($post)) {
                //ACTUALIZAR REGISTRO
                $post->update($params_array);

                //DEVOLVER RESPUESTA
                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post,
                    'changes' => $params_array
                );
            }
            /*
              $where = [
              'id' => $id,
              'user_id' => $user->sub
              ];
              $post = Post::updateOrCreate($where, $params_array); */
        }

        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request) {
        //CONSEGUIR USUARIO IDENTIFICADO
        $user = $this->getIdentity($request);


        //CONSEGUIR POST
        $post = Post::where('id', $id)
                ->where('user_id', $user->sub)
                ->first();

        if (!empty($post)) {
            //BORRARLO
            $post->delete();

            //DEVOLVER
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe.'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request) {
        //RECOGER LA IMAGEN DE LA PETICION
        $image = $request->file('file0');

        //VALIDAR IMAGEN 
        $validate = \Validator::make($request->all(), [
                    'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //GUARDAR LA IMAGEN EN DISCO
        if (!$image || $validate->fails()) {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            ];
        } else {
            $image_name = time() . $image->getClientOriginalName();

            \Storage::disk('images')->put($image_name, \File::get($image));

            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }

        //DEVOLVER DATOS
        return response()->json($data, $data['code']);
    }

    public function getImage($filename) {
        //COMPROBAR SI EXISTE EL FICHERO
        $isset = \Storage::disk('images')->exists($filename);

        if ($isset) {
            //CONSEGUIR LA IMAGEN
            $file = \Storage::disk('images')->get($filename);

            //DEVOLVER IMAGEN O MOSTRAR ERROR
            return new Response($file, 200);
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe.'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id) {
        $posts = Post::where('category_id', $id)->get();

        return response()->json([
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

    public function getPostsByUser($id) {
        $posts = Post::where('user_id', $id)->get();

        return response()->json([
                    'status' => 'success',
                    'posts' => $posts
        ]);
    }

    private function getIdentity($request) {
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }

}
