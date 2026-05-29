<?php

namespace App\Http\Controllers\Impuesto;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Sistema\Sucursal;
use App\Models\Impuesto\SiatCufd;
use App\Models\Impuesto\SiatCuis;
use Illuminate\Http\JsonResponse;
use App\Services\Siat\SiatService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Impuesto\SiatCliente;
use App\Models\Impuesto\SiatFactura;
use App\Services\Siat\SiatConstants;
use Illuminate\Support\Facades\Auth;
use App\Models\Impuesto\SiatProducto;
use Illuminate\Support\Facades\Storage;
use App\Services\Siat\FacturaXmlBuilder;
use App\Services\Siat\CufGeneratorService;
use App\Http\Requests\Impuesto\StoreFacturaEducativoRequest;

declare(strict_types=1);

namespace App\ThemeTest;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;
use Throwable;

/*
|--------------------------------------------------------------------------
| PHP Theme Test File
|--------------------------------------------------------------------------
| Archivo genérico para probar colores de sintaxis PHP en VSCode.
| Incluye: namespace, use, class, enum, trait, interface, attributes,
| strings, arrays, closures, match, try/catch, PHPDoc, heredoc, nowdoc,
| regex, nullable types, union types, readonly, named arguments y más.
|--------------------------------------------------------------------------
*/

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $method,
        public string $path,
        public ?string $name = null,
        public array $middleware = [],
    ) {}
}

enum EstadoFactura: string
{
    case BORRADOR = 'borrador';
    case EMITIDA = 'emitida';
    case PAGADA = 'pagada';
    case ANULADA = 'anulada';

    public function label(): string
    {
        return match ($this) {
            self::BORRADOR => 'Borrador',
            self::EMITIDA  => 'Emitida',
            self::PAGADA   => 'Pagada',
            self::ANULADA  => 'Anulada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BORRADOR => '#64748b',
            self::EMITIDA  => '#2563eb',
            self::PAGADA   => '#16a34a',
            self::ANULADA  => '#dc2626',
        };
    }
}

interface Exportable
{
    /**
     * Exporta el objeto actual como arreglo asociativo.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

interface RepositoryInterface
{
    public function find(int|string $id): ?FacturaDTO;

    /**
     * @return list<FacturaDTO>
     */
    public function all(): array;
}

trait HasLogger
{
    protected function log(string $message, array $context = []): void
    {
        $timestamp = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

        echo "[{$timestamp}] {$message}" . PHP_EOL;

        if ($context !== []) {
            print_r($context);
        }
    }
}

readonly class ClienteDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $documento,
        public ?string $email = null,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id'        => $this->id,
            'nombre'    => $this->nombre,
            'documento' => $this->documento,
            'email'     => $this->email,
        ];
    }
}

/**
 * @template TKey of array-key
 * @template TValue
 */
final class Collection
{
    /**
     * @param array<TKey, TValue> $items
     */
    public function __construct(
        private array $items = [],
    ) {}

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    public function filter(?callable $callback = null): self
    {
        return new self(array_filter($this->items, $callback));
    }

    public function first(): mixed
    {
        return $this->items[array_key_first($this->items)] ?? null;
    }

    public function all(): array
    {
        return $this->items;
    }
}

class FacturaDTO implements Exportable, JsonSerializable
{
    public function __construct(
        public int $id,
        public string $codigo,
        public ClienteDTO $cliente,
        public float $monto,
        public EstadoFactura $estado,
        public DateTimeImmutable $fecha,
        public ?string $observacion = null,
        public array $metadata = [],
    ) {}

    public function estaPagada(): bool
    {
        return $this->estado === EstadoFactura::PAGADA;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'codigo'      => $this->codigo,
            'cliente'     => $this->cliente->jsonSerialize(),
            'monto'       => $this->monto,
            'estado'      => $this->estado->value,
            'fecha'       => $this->fecha->format('Y-m-d H:i:s'),
            'observacion' => $this->observacion,
            'metadata'    => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

class FacturaRepository implements RepositoryInterface
{
    /**
     * @var list<FacturaDTO>
     */
    private array $facturas = [];

    public function __construct()
    {
        $this->facturas = [
            new FacturaDTO(
                id: 1,
                codigo: 'FAC-0001',
                cliente: new ClienteDTO(
                    id: 10,
                    nombre: 'Juan Pérez',
                    documento: '1234567',
                    email: 'juan@example.com',
                ),
                monto: 1500.75,
                estado: EstadoFactura::PAGADA,
                fecha: new DateTimeImmutable('2026-05-29 10:30:00'),
                observacion: 'Factura pagada correctamente.',
                metadata: [
                    'origen' => 'web',
                    'tags' => ['siat', 'educativo', 'demo'],
                    'debug' => true,
                ],
            ),
            new FacturaDTO(
                id: 2,
                codigo: 'FAC-0002',
                cliente: new ClienteDTO(
                    id: 11,
                    nombre: 'Empresa Demo SRL',
                    documento: '987654321',
                ),
                monto: 299.99,
                estado: EstadoFactura::EMITIDA,
                fecha: new DateTimeImmutable(),
                metadata: [
                    'origen' => 'api',
                    'moneda' => 'BOB',
                ],
            ),
        ];
    }

    public function find(int|string $id): ?FacturaDTO
    {
        foreach ($this->facturas as $factura) {
            if ((string) $factura->id === (string) $id) {
                return $factura;
            }
        }

        return null;
    }

    public function all(): array
    {
        return $this->facturas;
    }
}

#[Route(method: 'GET', path: '/facturas', name: 'facturas.index')]
class FacturaController
{
    use HasLogger;

    public function __construct(
        private readonly RepositoryInterface $repository,
    ) {}

    #[Route(method: 'GET', path: '/facturas/{id}', name: 'facturas.show')]
    public function show(int|string $id): array
    {
        $this->log('Buscando factura', [
            'id' => $id,
        ]);

        $factura = $this->repository->find($id);

        if ($factura === null) {
            throw new RuntimeException("Factura {$id} no encontrada.");
        }

        return [
            'success' => true,
            'data' => $factura->toArray(),
        ];
    }

    public function index(?string $estado = null): array
    {
        $facturas = new Collection($this->repository->all());

        $filtradas = $facturas->filter(
            fn (FacturaDTO $factura): bool =>
                $estado === null || $factura->estado->value === $estado
        );

        return [
            'success' => true,
            'count' => count($filtradas->all()),
            'data' => array_map(
                callback: fn (FacturaDTO $factura): array => $factura->toArray(),
                array: $filtradas->all(),
            ),
        ];
    }
}

final class StringTester
{
    public const REGEX_EMAIL = '/^[\w\.-]+@[\w\.-]+\.\w{2,}$/i';

    public static function demo(): void
    {
        $simple = 'String simple con $variable sin interpolar';
        $double = "String doble con fecha: " . date('Y-m-d H:i:s');

        $multiline = <<<HTML
        <section class="card">
            <h1>Factura Demo</h1>
            <p>Total: Bs 1500.75</p>
        </section>
        HTML;

        $raw = <<<'SQL'
        SELECT *
        FROM facturas
        WHERE estado = 'pagada'
          AND monto_total >= 1000
        ORDER BY fecha_emision DESC;
        SQL;

        $json = <<<JSON
        {
            "success": true,
            "message": "Tema VSCode probado correctamente",
            "items": [1, 2, 3]
        }
        JSON;

        echo $simple;
        echo $double;
        echo $multiline;
        echo $raw;
        echo $json;

        preg_match(self::REGEX_EMAIL, 'demo@example.com', $matches);
    }
}

function calcularImpuesto(
    float $monto,
    float $porcentaje = 13.0,
    bool $redondear = true,
): float {
    $resultado = $monto * ($porcentaje / 100);

    return $redondear
        ? round($resultado, 2)
        : $resultado;
}

function validarDocumento(string $documento): bool
{
    return match (true) {
        preg_match('/^\d{5,12}$/', $documento) === 1 => true,
        preg_match('/^[A-Z]{2}-\d{4}$/', $documento) === 1 => true,
        default => false,
    };
}

try {
    $repository = new FacturaRepository();

    $controller = new FacturaController(
        repository: $repository,
    );

    $response = $controller->index(
        estado: EstadoFactura::PAGADA->value,
    );

    echo json_encode(
        value: $response,
        flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    );

    $factura = $controller->show(id: 1);

    $monto = $factura['data']['monto'] ?? 0;
    $iva = calcularImpuesto((float) $monto);

    echo PHP_EOL . "IVA calculado: {$iva}" . PHP_EOL;

    StringTester::demo();
} catch (InvalidArgumentException $e) {
    echo 'Argumento inválido: ' . $e->getMessage();
} catch (RuntimeException | Exception $e) {
    echo 'Error controlado: ' . $e->getMessage();
} catch (Throwable $e) {
    echo 'Error inesperado: ' . $e->getMessage();
} finally {
    echo PHP_EOL . 'Fin del test de sintaxis PHP.' . PHP_EOL;
}
