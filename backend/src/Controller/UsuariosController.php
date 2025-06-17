<?php

namespace App\Controller;

use App\Entity\Roles;
use App\Entity\Videojuegos;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Entity\Usuarios;

#[Route('/api/usuarios', name: 'usuarios_')]
final class UsuariosController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        $usuario = $em->getRepository(Usuarios::class)->findOneBy(['email' => $email, 'deleted' => false]);
        if (!$usuario || !password_verify($password, $usuario->getPassword())) {
            return new JsonResponse(['error' => 'Email y/o contraseña incorrectos']);
        }

        $payload = [
            'id' => $usuario->getId(),
            'apellido' => $usuario->getApellido(),
            'email' => $usuario->getEmail(),
            'telefono' => $usuario->getTelefono(),
            'direccion' => $usuario->getDireccion(),
        ];
        $token = $jwtManager->createFromPayload($usuario, $payload);

        return new JsonResponse(['token' => $token]);
    }

    #[Route('/registro', name: 'app_registro', methods: ['POST'])]
    public function registro(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $required = ['nombre', 'apellido', 'email', 'password', 'telefono', 'direccion'];
        foreach ($required as $field) {
            if (empty($data[$field]) || !isset($data[$field]) || trim($data[$field]) === '') {
                return new JsonResponse(['error' => "Falta el campo $field"]);
            }
        }

        // Comprobar si el email ya existe
        if ($em->getRepository(Usuarios::class)->findOneBy(['email' => $data['email']])) {
            return new JsonResponse(['error' => 'El email ya está registrado']);
        }

        $usuario = new Usuarios();
        // Asignar rol
        $rol = $em->getRepository(Roles::class)->findOneBy(['nombre' => 'cliente']);
        if (!$rol) {
            return new JsonResponse(['error' => 'Rol no válido']);
        }
        $usuario->setRol($rol);

        $usuario->setNombre($data['nombre']);
        $usuario->setApellido($data['apellido']);
        $usuario->setEmail($data['email']);
        $usuario->setTelefono($data['telefono']);
        $usuario->setDireccion($data['direccion']);
        $usuario->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $usuario->setCarrito([]);
        $usuario->setDeleted(false);
        $usuario->setCreatedAt(new \DateTime());
        $usuario->setCreatedBy(null); // Asignar creador por defecto
        $usuario->setModifiedAt(new \DateTime());
        $usuario->setModifiedBy(null); // Asignar modificador por defecto

        $em->persist($usuario);
        $em->flush();

        $payload = [
            'id' => $usuario->getId(),
            'apellido' => $usuario->getApellido(),
            'email' => $usuario->getEmail(),
            'telefono' => $usuario->getTelefono(),
            'direccion' => $usuario->getDireccion(),
        ];
        $token = $jwtManager->createFromPayload($usuario, $payload);

        return new JsonResponse(['token' => $token]);
    }

    #[Route('/listarUsuarios', name: 'listar_usuarios', methods: ['POST'])]
    public function listarUsuarios(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }

        $usuarios = $em->getRepository(Usuarios::class)->findAll();
        $result = [];
        foreach ($usuarios as $usuario) {
            $result[] = [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre(),
                'apellido' => $usuario->getApellido(),
                'email' => $usuario->getEmail(),
                'telefono' => $usuario->getTelefono(),
                'direccion' => $usuario->getDireccion(),
                'rol' => $usuario->getRol()->getNombre(),
                'createdAt' => $usuario->getCreatedAt()->format('d/m/Y H:i:s'),
                'modifiedAt' => $usuario->getModifiedAt() ? $usuario->getModifiedAt()->format('d/m/Y H:i:s') : null,
                'deleted' => $usuario->isDeleted(),
                'createdBy' => $usuario->getCreatedBy() ? $usuario->getCreatedBy()->getNombre() . " " . $usuario->getCreatedBy()->getApellido() : null,
                'modifiedBy' => $usuario->getModifiedBy() ? $usuario->getModifiedBy()->getNombre() . " " . $usuario->getModifiedBy()->getApellido() : null,
            ];
        }
        return new JsonResponse($result);
    }

    #[Route('/listarRoles', name: 'listar_roles', methods: ['POST'])]
    public function listarRoles(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $jwtManager->parse($token); // Lanza excepción si el token no es válido
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $roles = $em->getRepository(Roles::class)->findAll();
        $result = [];
        foreach ($roles as $rol) {
            $result[] = [
                'id' => $rol->getId(),
                'nombre' => $rol->getNombre(),
            ];
        }
        return new JsonResponse($result);
    }

    #[Route('/editarUsuario', name: 'editar_usuario', methods: ['PUT'])]
    public function editarUsuario(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $id = $data['id'] ?? null;
        if (!$id) {
            return new JsonResponse(['error' => 'ID de usuario no proporcionado'], 400);
        }
        $required = ['nombre', 'apellido', 'email', 'telefono', 'direccion'];
        foreach ($required as $field) {
            if (empty($data[$field]) || !isset($data[$field]) || trim($data[$field]) === '') {
                return new JsonResponse(['error' => "Falta el campo $field"]);
            }
        }
        $usuario = $em->getRepository(Usuarios::class)->find($id);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }
        // Actualizar campos editables
        $usuario->setNombre(trim($data['nombre']) ?? $usuario->getNombre());
        $usuario->setApellido(trim($data['apellido']) ?? $usuario->getApellido());
        $usuario->setEmail(trim($data['email']) ?? $usuario->getEmail());
        $usuario->setTelefono(trim($data['telefono']) ?? $usuario->getTelefono());
        $usuario->setDireccion(trim($data['direccion']) ?? $usuario->getDireccion());
        $usuario->setDeleted(isset($data['deleted']) ? filter_var($data['deleted'], FILTER_VALIDATE_BOOLEAN) : $usuario->isDeleted());
        // Rol
        if (isset($data['rol'])) {
            $rol = $em->getRepository(Roles::class)->findOneBy(['nombre' => $data['rol']]);
            if ($rol) {
                $usuario->setRol($rol);
            }
        }
        $usuarioModificador = $em->getRepository(Usuarios::class)->find($userData['id']);
        if ($usuarioModificador) {
            $usuario->setModifiedBy($usuarioModificador); // Asignar el usuario que modifica
        }
        $usuario->setModifiedAt(new \DateTime());
        $em->flush();
        return new JsonResponse(['message' => "Usuario actualizado correctamente"]);
    }

    #[Route('/datosPersonales', name: 'datos_personales', methods: ['POST'])]
    public function datosPersonales(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($userData['id'] ?? 0);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }
        return new JsonResponse([
            'id' => $usuario->getId(),
            'nombre' => $usuario->getNombre(),
            'apellido' => $usuario->getApellido(),
            'email' => $usuario->getEmail(),
            'telefono' => $usuario->getTelefono(),
            'direccion' => $usuario->getDireccion(),
        ]);
    }

    #[Route('/cambiarContrasena', name: 'cambiar_contrasena', methods: ['PUT'])]
    public function cambiarContrasena(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $actual = $data['actual'] ?? null;
        $nueva = $data['nueva'] ?? null;
        if (!$token || !$actual || !$nueva) {
            return new JsonResponse(['error' => 'Faltan datos'], 400);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($userData['id'] ?? 0);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }
        if (!password_verify($actual, $usuario->getPassword())) {
            return new JsonResponse(['error' => 'La contraseña actual no es correcta']);
        }

        $usuario->setPassword(password_hash($nueva, PASSWORD_BCRYPT));
        $usuario->setModifiedAt(new \DateTime());
        $usuarioModificador = $em->getRepository(Usuarios::class)->find($userData['id']);
        if ($usuarioModificador) {
            $usuario->setModifiedBy($usuarioModificador); // Asignar el usuario que modifica
        }
        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/obtenerCarrito', name: 'obtener_carrito', methods: ['POST'])]
    public function obtenerCarrito(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($userData['id'] ?? 0);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }
        $carrito = $usuario->getCarrito() ?? [];
        $result = [];
        foreach ($carrito as $item) {
            $videojuego = $em->getRepository(Videojuegos::class)->findOneBy(["id" => $item['videojuego_id'], "deleted" => false]);
            if ($videojuego) {
                $result[] = [
                    'videojuego_id' => $item['videojuego_id'],
                    'nombre' => $videojuego->getNombre(),
                    'plataforma' => $videojuego->getPlataforma() ? $videojuego->getPlataforma()->getNombre() : null,
                    'precio' => $videojuego->getPrecio(),
                    'cantidad' => $item['cantidad']
                ];
            }
        }
        return new JsonResponse($result);
    }

    #[Route('/eliminarDeCarrito', name: 'eliminar_carrito', methods: ['POST'])]
    public function eliminarDelCarrito(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $videojuegoId = $data['videojuegoId'] ?? null;
        if (!$token || !$videojuegoId) {
            return new JsonResponse(['error' => 'Faltan datos'], 400);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($userData['id'] ?? 0);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }
        $carrito = $usuario->getCarrito() ?? [];
        $nuevoCarrito = array_filter($carrito, function ($item) use ($videojuegoId) {
            return $item['videojuego_id'] != $videojuegoId;
        });
        $usuario->setCarrito(array_values($nuevoCarrito));
        $em->persist($usuario);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }
}