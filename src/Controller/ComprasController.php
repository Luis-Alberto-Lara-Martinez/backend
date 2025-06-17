<?php

namespace App\Controller;

use App\Entity\Compras;
use App\Entity\DetallesCompra;
use App\Entity\Usuarios;
use App\Entity\Videojuegos;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/compras', name: 'usuarios_')]
final class ComprasController extends AbstractController
{
    #[Route('/historial', name: 'historial_compras', methods: ['POST'])]
    public function historialCompras(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $userToken = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }
        $usuario = $em->getRepository(Usuarios::class)->find($userToken['id']);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], 404);
        }
        $compras = $em->getRepository(Compras::class)->findBy(["usuario" => $usuario->getId()], ['fecha' => 'DESC']);
        $result = [];
        foreach ($compras as $compra) {
            $detalles = [];
            foreach ($compra->getDetallesCompras() as $detalle) {
                $detalles[] = [
                    'videojuego' => $detalle->getVideojuego()->getNombre(),
                    'plataforma' => $detalle->getVideojuego()->getPlataforma()->getNombre(),
                    'cantidad' => $detalle->getCantidad(),
                    'precio_unitario' => $detalle->getPrecioUnitario()
                ];
            }
            $result[] = [
                'usuario' => $usuario->getNombre() . " " . $usuario->getApellido(),
                'transaccion_id' => $compra->getTransaccionId(),
                'fecha' => $compra->getFecha()->format('d/m/Y H:i:s'),
                'precio_total' => $compra->getPrecioTotal(),
                'detalles' => $detalles
            ];
        }
        return new JsonResponse($result);
    }

    #[Route('/crear', name: 'crear_compra', methods: ['POST'])]
    public function crearCompras(Request $request, EntityManagerInterface $em, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        if (!$token) {
            return new JsonResponse(['error' => 'Token no proporcionado'], 401);
        }
        try {
            $userToken = $jwtManager->parse($token);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token inválido'], 401);
        }

        $usuario = $em->getRepository(Usuarios::class)->find($userToken['id']);

        $compra = new Compras();
        $compra->setUsuario($usuario);
        $compra->setPrecioTotal($data['precio_total']);
        $compra->setFecha(new \DateTime());
        $compra->setTransaccionId($data['transaccion_id']);

        $em->persist($compra);

        foreach ($data["videojuegos"] as $videojuego) {
            $videojuegoDB = $em->getRepository(Videojuegos::class)->find($videojuego["videojuego_id"]);
            $videojuegoDB->setStock($videojuegoDB->getStock() - $videojuego["cantidad"]);
            if ($videojuegoDB->getStock() == 0) {
                $videojuegoDB->setDeleted(true);
            }

            $em->persist($videojuegoDB);

            $detalleCompra = new DetallesCompra();
            $detalleCompra->setCompra($compra);
            $detalleCompra->setVideojuego($videojuegoDB);
            $detalleCompra->setCantidad($videojuego["cantidad"]);
            $detalleCompra->setPrecioUnitario($videojuego["precio_unitario"]);

            $em->persist($detalleCompra);
        }

        $usuario->setCarrito([]);
        $em->flush();
        return new JsonResponse(["message" => "Compra creada correctamente"]);
    }
}
