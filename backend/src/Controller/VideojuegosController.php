<?php

namespace App\Controller;

use App\Entity\Categorias;
use App\Entity\Imagenes;
use App\Entity\Plataformas;
use App\Entity\Reviews;
use App\Entity\Usuarios;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Entity\Videojuegos;

#[Route('/api/videojuegos', name: 'videojuegos_')]
final class VideojuegosController extends AbstractController
{
    #[Route('/listarVideojuegos', name: 'app_listar_videojuegos', methods: ['POST'])]
    public function listarVideojuegos(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $tokenUser = $jwtManager->parse($token); // Lanza excepción si el token no es válido
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $videojuegos = $em->getRepository(Videojuegos::class)->findBy(["deleted" => false]);
        $result = [];
        foreach ($videojuegos as $videojuego) {
            $imagenes = [];
            foreach ($videojuego->getImagenes() as $img) {
                $imagenes[] = [
                    'url' => $img->getUrl(),
                    'portada' => $img->isPortada()
                ];
            }
            $categorias = [];
            foreach ($videojuego->getCategoria() as $cat) {
                $categorias[] = $cat->getNombre();
            }
            $result[] = [
                'id' => $videojuego->getId(),
                'nombre' => $videojuego->getNombre(),
                'nota_media' => $videojuego->getNotaMedia(),
                'deleted' => $videojuego->isDeleted(),
                'precio' => $videojuego->getPrecio(),
                'fecha_lanzamiento' => $videojuego->getFechaLanzamiento()->format('d/m/Y'),
                'stock' => $videojuego->getStock(),
                'imagenes' => $imagenes,
                'categorias' => $categorias,
                'plataforma' => $videojuego->getPlataforma()?->getNombre(),
                'fecha_creacion' => $videojuego->getCreatedAt()->format('d/m/Y'),
            ];
        }
        return new JsonResponse($result);
    }

    #[Route('/listarVideojuegosAdmin', name: 'app_listar_videojuegos_admin', methods: ['POST'])]
    public function listarVideojuegosAdmin(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $tokenUser = $jwtManager->parse($token); // Lanza excepción si el token no es válido
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }

        $videojuegos = $em->getRepository(Videojuegos::class)->findAll();
        $result = [];
        foreach ($videojuegos as $videojuego) {
            $imagenes = [];
            foreach ($videojuego->getImagenes() as $img) {
                $imagenes[] = [
                    'url' => $img->getUrl(),
                    'portada' => $img->isPortada()
                ];
            }
            $categorias = [];
            foreach ($videojuego->getCategoria() as $cat) {
                $categorias[] = $cat->getNombre();
            }
            $result[] = [
                'id' => $videojuego->getId(),
                'nombre' => $videojuego->getNombre(),
                'deleted' => $videojuego->isDeleted(),
                'nota_media' => $videojuego->getNotaMedia(),
                'precio' => $videojuego->getPrecio(),
                'fecha_lanzamiento' => $videojuego->getFechaLanzamiento()->format('d/m/Y'),
                'stock' => $videojuego->getStock(),
                'imagenes' => $imagenes,
                'categorias' => $categorias,
                'plataforma' => $videojuego->getPlataforma()?->getNombre(),
                'fecha_creacion' => $videojuego->getCreatedAt()->format('d/m/Y'),
            ];
        }
        return new JsonResponse($result);
    }

    #[Route('/crear', name: 'crear_videojuego', methods: ['POST'])]
    public function crearVideojuego(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $tokenUser = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($tokenUser["id"]);

        // Buscar la plataforma
        $plataforma = $em->getRepository(Plataformas::class)->findOneBy(["nombre" => $data['plataforma']]);
        if (!$plataforma) {
            $plataforma = new Plataformas();
            $plataforma->setNombre($data['plataforma']);
            $em->persist($plataforma);
        }

        // ❗ Comprobación: si ya existe un videojuego con ese nombre y plataforma
        $existente = $em->getRepository(Videojuegos::class)->findOneBy([
            'nombre' => $data['nombre'],
            'plataforma' => $plataforma
        ]);

        if ($existente) {
            return new JsonResponse(['error' => 'Videojuego ya existente']);
        }

        // Crear el nuevo videojuego
        $videojuego = new Videojuegos();
        $videojuego->setCreatedBy($usuario);
        $videojuego->setModifiedBy($usuario);
        $videojuego->setNombre($data['nombre']);
        $videojuego->setPrecio($data['precio']);
        $videojuego->setFechaLanzamiento(new \DateTime($data['fecha_lanzamiento']));
        $videojuego->setStock($data['stock']);
        $videojuego->setDeleted(false);
        $videojuego->setModifiedAt(new \DateTime());
        $videojuego->setCreatedAt(new \DateTime());
        $videojuego->setPlataforma($plataforma);

        foreach ($data["categorias"] as $cat) {
            $categoria = $em->getRepository(Categorias::class)->findOneBy(["nombre" => $cat]);
            if (!$categoria) {
                $categoria = new Categorias();
                $categoria->setNombre($cat);
                $em->persist($categoria);
            }

            if (!$videojuego->getCategoria()->contains($categoria)) {
                $videojuego->addCategoria($categoria);
            }
        }

        $em->persist($videojuego);

        foreach ($data["imagenes"] as $imagen) {
            $img = new Imagenes();
            $img->setVideojuego($videojuego);
            $img->setUrl($imagen["url"]);
            $img->setPortada($imagen["portada"]);
            $em->persist($img);
        }

        $em->flush();

        return new JsonResponse(['message' => "Videojuego agregado correctamente"]);
    }

    #[Route('/editar/{id}', name: 'editar_videojuego', methods: ['PUT'])]
    public function editarVideojuego($id, Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $tokenUser = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $videojuego = $em->getRepository(Videojuegos::class)->find($id);
        if (!$videojuego) {
            return new JsonResponse(['error' => 'Videojuego no encontrado'], 404);
        }

        $videojuego->setPrecio($data['precio'] ?? $videojuego->getPrecio());
        $videojuego->setDeleted($data['deleted'] ?? $videojuego->isDeleted());
        $videojuego->setStock($data['stock'] ?? $videojuego->getStock());
        $videojuego->setModifiedBy($em->getRepository(Usuarios::class)->find($tokenUser["id"]));
        $videojuego->setModifiedAt(new \DateTime());
        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/eliminar/{id}', name: 'eliminar_videojuego', methods: ['DELETE'])]
    public function eliminarVideojuego($id, Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $tokenUser = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $videojuego = $em->getRepository(Videojuegos::class)->find($id);
        if (!$videojuego) {
            return new JsonResponse(['error' => 'Videojuego no encontrado'], 404);
        }
        $videojuego->setDeleted(true);
        $videojuego->setModifiedAt(new \DateTime());
        $videojuego->setModifiedBy($em->getRepository(Usuarios::class)->find($tokenUser["id"]));
        $em->flush();
        return new JsonResponse(['message' => "Videojuego eliminado correctamente"]);
    }

    #[Route('/insertarAlCarrito', name: 'insertar_carrito', methods: ['POST'])]
    public function insertarAlCarrito(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $videojuegoId = $data['videojuego_id'] ?? null;
        $cantidad = $data['cantidad'] ?? 1;

        if (!$token || !$videojuegoId) {
            return new JsonResponse(['error' => 'Faltan datos'], 400);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($userData['id'] ?? 0);
        $videojuego = $em->getRepository(Videojuegos::class)->find($videojuegoId);
        if (!$usuario || !$videojuego) {
            return new JsonResponse(['error' => 'Usuario o videojuego no encontrado'], 404);
        }

        $carrito = $usuario->getCarrito() ?? [];
        $stockDisponible = $videojuego->getStock();
        $encontrado = false;

        // Iterate by reference to modify the original array elements
        foreach ($carrito as &$item) { //
            if ($item['videojuego_id'] == $videojuegoId) { //
                if ($item['cantidad'] + $cantidad > $stockDisponible) { //
                    return new JsonResponse(['error' => 'No hay suficiente stock disponible']); //
                }
                $item['cantidad'] += $cantidad; //
                $encontrado = true; //
                break; // Exit loop once found and updated
            }
        }
        unset($item); // Break the reference to avoid unintended side effects
        if (!$encontrado) { //
            if ($cantidad > $stockDisponible) { //
                return new JsonResponse(['error' => 'No hay suficiente stock disponible']); //
            }
            $carrito[] = [ //
                'videojuego_id' => $videojuegoId, //
                'cantidad' => $cantidad //
            ];
        }

        $usuario->setCarrito($carrito); //
        $em->persist($usuario); //
        $em->flush(); //
        return new JsonResponse(['message' => "Videojuego agregado al carrito correctamente"]); //
    }

    #[Route('/plataformas', name: 'obtener_plataformas', methods: ['POST'])]
    public function obtenerPlataformas(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $plataformas = $em->getRepository(Plataformas::class)->findAll();
        $result = [];
        foreach ($plataformas as $plataforma) {
            $result[] = $plataforma->getNombre();
        }
        return new JsonResponse($result);
    }

    #[Route('/categorias', name: 'obtener_categorias', methods: ['POST'])]
    public function obtenerCategorias(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $categorias = $em->getRepository(Categorias::class)->findAll();
        $result = [];
        foreach ($categorias as $categoria) {
            $result[] = $categoria->getNombre();
        }
        return new JsonResponse($result);
    }

    #[Route('/filtros', name: 'filtros', methods: ['POST'])]
    public function filtros(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }

        // Se usa el QueryBuilder para una consulta dinámica y eficiente
        $qb = $em->getRepository(Videojuegos::class)->createQueryBuilder('v');

        // Solo videojuegos no eliminados
        $qb->where('v.deleted = :deleted')->setParameter('deleted', false);

        // Filtro por título (búsqueda parcial)
        if (!empty($data['titulo'])) {
            $qb->andWhere('v.nombre LIKE :titulo')
                ->setParameter('titulo', '%' . $data['titulo'] . '%');
        }

        // Filtro por plataforma
        if (!empty($data['plataforma'])) {
            $qb->innerJoin('v.plataforma', 'p')
                ->andWhere('p.nombre = :plataforma')
                ->setParameter('plataforma', $data['plataforma']);
        }

        // Filtro por categoría
        if (!empty($data['categoria'])) {
            $qb->innerJoin('v.categoria', 'c')
                ->andWhere('c.nombre = :categoria')
                ->setParameter('categoria', $data['categoria']);
        }

        // Orden por precio
        if (!empty($data['orden']) && in_array($data['orden'], ['ASC', 'DESC'])) {
            $qb->orderBy('v.precio', $data['orden']);
        }

        $videojuegos = $qb->getQuery()->getResult();

        $result = [];
        foreach ($videojuegos as $videojuego) {
            $imagenes = [];
            foreach ($videojuego->getImagenes() as $img) {
                $imagenes[] = [
                    'url' => $img->getUrl(),
                    'portada' => $img->isPortada()
                ];
            }
            $categorias = [];
            foreach ($videojuego->getCategoria() as $cat) {
                $categorias[] = $cat->getNombre();
            }
            $result[] = [
                'id' => $videojuego->getId(),
                'nombre' => $videojuego->getNombre(),
                'deleted' => $videojuego->isDeleted(),
                'precio' => $videojuego->getPrecio(),
                'fecha_lanzamiento' => $videojuego->getFechaLanzamiento()->format('d/m/Y'),
                'stock' => $videojuego->getStock(),
                'imagenes' => $imagenes,
                'categorias' => $categorias,
                'plataforma' => $videojuego->getPlataforma()?->getNombre(),
            ];
        }
        return new JsonResponse($result);
    }

    #[Route('/listarReviews', name: 'listar_resenas', methods: ['POST'])]
    public function listarResenas(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $productoId = $data['productoId'] ?? null;
        if (!$token || !$productoId) {
            return new JsonResponse(['error' => 'Faltan datos'], 400);
        }
        try {
            $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $videojuego = $em->getRepository(Videojuegos::class)->find($productoId);
        if (!$videojuego) {
            return new JsonResponse(['error' => 'Videojuego no encontrado'], 404);
        }
        $resenas = $em->getRepository(Reviews::class)->findBy(["videojuego" => $videojuego], ["created_at" => "DESC"]); // Asumiendo relación getResenas()
        $result = [];
        foreach ($resenas as $resena) {
            $result[] = [
                'usuario' => $resena->getUsuario()->getNombre() . " " . $resena->getUsuario()->getApellido(),
                'comentario' => $resena->getComentario(),
                'nota' => $resena->getNota(),
                'fecha' => $resena->getCreatedAt()->format("d/m/Y H:i:s")
            ];
        }
        return new JsonResponse($result);
    }

    #[Route('/crearReview', name: 'crear_review', methods: ['POST'])]
    public function crearResena(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $productoId = $data['videojuego_id'] ?? null;
        $comentario = $data['comentario'] == "" ? null : $data['comentario'];
        $puntuacion = $data['nota'] ?? null;
        if (!$token || !$productoId || !$puntuacion) {
            return new JsonResponse(['error' => 'Faltan datos'], 400);
        }
        try {
            $userData = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($userData['id'] ?? 0);
        $videojuego = $em->getRepository(Videojuegos::class)->find($productoId);
        if (!$usuario || !$videojuego) {
            return new JsonResponse(['error' => 'Usuario o videojuego no encontrado'], 404);
        }
        $resena = new Reviews();
        $resena->setUsuario($usuario);
        $resena->setVideojuego($videojuego);
        $resena->setComentario($comentario);
        $resena->setNota($puntuacion);
        $resena->setCreatedAt(new \DateTime());
        $em->persist($resena);
        $em->flush();

        $sumaNotas = 0;
        $contadorReviews = 0;
        foreach ($videojuego->getReviews() as $review) {
            $contadorReviews++;
            $sumaNotas += $review->getNota();
        }
        if ($contadorReviews > 0) {
            $notaMedia = $sumaNotas / $contadorReviews;
        } else {
            $notaMedia = 0;
        }
        $videojuego->setNotaMedia(round($notaMedia, 2));
        $em->flush();
        return new JsonResponse(['message' => "Reseña creada correctamente"]);
    }

    #[Route('/ultimasNovedades', name: 'ultimas_novedades', methods: ['POST'])]
    public function ultimasNovedades(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $videojuegos = $em->getRepository(Videojuegos::class)->findBy(["deleted" => false], ["created_at" => "DESC"], 6);
        $result = [];
        foreach ($videojuegos as $videojuego) {
            $imagenes = [];
            foreach ($videojuego->getImagenes() as $img) {
                $imagenes[] = [
                    'url' => $img->getUrl(),
                    'portada' => $img->isPortada()
                ];
            }
            $categorias = [];
            foreach ($videojuego->getCategoria() as $cat) {
                $categorias[] = $cat->getNombre();
            }
            $result[] = [
                'id' => $videojuego->getId(),
                'nombre' => $videojuego->getNombre(),
                'deleted' => $videojuego->isDeleted(),
                'nota_media' => $videojuego->getNotaMedia(),
                'precio' => $videojuego->getPrecio(),
                'fecha_lanzamiento' => $videojuego->getFechaLanzamiento()->format('d/m/Y'),
                'stock' => $videojuego->getStock(),
                'imagenes' => $imagenes,
                'categorias' => $categorias,
                'plataforma' => $videojuego->getPlataforma()?->getNombre(),
            ];
        }
        return new JsonResponse($result);
    }
}