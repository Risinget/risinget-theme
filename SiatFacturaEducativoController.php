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

class SiatFacturaEducativoController extends Controller
{
    public function __construct(
        private readonly SiatService         $siat,
        private readonly CufGeneratorService $cufGenerator,
        private readonly FacturaXmlBuilder   $xmlBuilder,
    ) {}

    // ─── CUIS / CUFD ────────────────────────────────────────────────

    /**
     * Obtiene el CUIS vigente o solicita uno nuevo si está próximo a expirar.
     */
    public function ultimoCuis(): ?string
    {
        $ultimoCuis = SiatCuis::latest('id')->first();
        $nuevoCuis  = $ultimoCuis?->cuis;

        // Si existe y su vigencia es mayor a 1 día, reutilizarlo
        if ($ultimoCuis && now()->addDay()->lessThan($ultimoCuis->fechaVigencia)) {
            return $nuevoCuis;
        }

        // Solicitar un nuevo CUIS a la API
        try {
            $res = $this->siat->codigos->cuis(
                codigoAmbiente:   $this->siat->config->codigoAmbiente,
                codigoModalidad:  $this->siat->config->codigoModalidad,
                codigoPuntoVenta: $this->siat->config->codigoPuntoVenta,
                codigoSistema:    $this->siat->config->codigoSistema,
                codigoSucursal:   $this->siat->config->codigoSucursal,
                nit:              $this->siat->config->nit,
            );

            if (isset($res['data']->RespuestaCuis->codigo)) {
                $modelo = SiatCuis::create([
                    'cuis'          => $res['data']->RespuestaCuis->codigo,
                    'fechaVigencia' => date('Y-m-d H:i:s', strtotime($res['data']->RespuestaCuis->fechaVigencia)),
                ]);

                return $modelo->cuis;
            }
        } catch (\Throwable $e) {
            Log::error('Error al solicitar CUIS al SIAT', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        // Fallback: usar el anterior si existe
        return $nuevoCuis;
    }

    /**
     * Obtiene el CUFD vigente o solicita uno nuevo si ya expiró.
     */
    public function ultimoCufd(): ?SiatCufd
    {
        $ultimoCufd    = SiatCufd::latest()->first();

        $fechaVigencia = $ultimoCufd?->fechaVigencia
        ? Carbon::parse($ultimoCufd->fechaVigencia)
        : null;

        // Si el CUFD existe y todavía es vigente, retornarlo directamente
        if ($ultimoCufd && !is_null($fechaVigencia) && $fechaVigencia > now()) {
            return $ultimoCufd;
        }

        // Si no existe o ya expiró, solicitar uno nuevo a la API
        try {
            $responseCufd = $this->siat->codigos->cufd(
                codigoAmbiente:   $this->siat->config->codigoAmbiente,
                codigoModalidad:  $this->siat->config->codigoModalidad,
                codigoPuntoVenta: $this->siat->config->codigoPuntoVenta,
                codigoSistema:    $this->siat->config->codigoSistema,
                codigoSucursal:   $this->siat->config->codigoSucursal,
                cuis:             $this->ultimoCuis(),
                nit:              $this->siat->config->nit,
            );

            if ($responseCufd['data']->RespuestaCufd->codigo) {
                return SiatCufd::create([
                    'codigo'        => $responseCufd['data']->RespuestaCufd->codigo,
                    'codigoControl' => $responseCufd['data']->RespuestaCufd->codigoControl,
                    'direccion'     => $responseCufd['data']->RespuestaCufd->direccion,
                    'fechaVigencia' => $responseCufd['data']->RespuestaCufd->fechaVigencia,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Error al solicitar CUFD al SIAT', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }

    // ─── INDEX ───────────────────────────────────────────────────────

    public function index()
    {
        $statusSiat = 'NO ACTIVO';

        try {
            $response   = $this->siat->codigos->verificarComunicacion();
            $statusSiat = $response['data']->RespuestaComunicacion->mensajesList->descripcion ?? 'NO ACTIVO';
        } catch (\Throwable $e) {
            Log::warning('No se pudo verificar comunicación SIAT', ['error' => $e->getMessage()]);
        }

        $sucursales = Sucursal::all();
        $productos  = SiatProducto::all();
        $codigoCUIS = $this->ultimoCuis();
        $cufdModel  = $this->ultimoCufd();
        $codigoCUFD = $cufdModel?->codigoControl;
        $nit        = (string) $this->siat->config->nit;

        return view('main_1.impuesto.facturaeducativo.index', compact(
            'sucursales',
            'nit',
            'productos',
            'codigoCUIS',
            'codigoCUFD',
            'statusSiat',
        ));
    }

    // ─── BUSCAR CLIENTE ─────────────────────────────────────────────

    public function buscarCliente(Request $request): JsonResponse
    {
        $request->validate([
            'NumeroDocumento'              => 'required|string',
            'CodigoTipoDocumentoIdentidad' => 'required|string',
        ]);

        $cliente = SiatCliente::where('NumeroDocumento', $request->input('NumeroDocumento'))
            ->where('CodigoTipoDocumentoIdentidad', $request->input('CodigoTipoDocumentoIdentidad'))
            ->first();

        return response()->json([
            'success' => (bool) $cliente,
            'data'    => $cliente,
        ]);
    }

    // ─── STORE (Emisión de Factura) ─────────────────────────────────

    public function store(StoreFacturaEducativoRequest $request): JsonResponse
    {
        // 1. Obtener leyenda aleatoria
        $leyenda = $this->obtenerLeyendaAleatoria();
        if ($leyenda === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la leyenda del SIAT. Recargue e intente de nuevo.',
            ], 503);
        }

        // 2. Obtener CUFD vigente
        $cufdModel = $this->ultimoCufd();
        if (!$cufdModel) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener un CUFD vigente. Intente nuevamente.',
            ], 503);
        }

        // 3. Datos del emisor
        $sucursal          = Sucursal::find(1);
        $sucursalDireccion = $sucursal?->direccion ?? '';
        $usuarioActual     = Auth::user()->usuario;

        // TODO: Implementar correlativo real de facturas (tabla/secuencia)
        $numeroFacturaNormal = 2;

        // 4. Generar CUF
        $fecha    = Carbon::now();
        $cufData  = $this->cufGenerator->generar(
            nit:               $this->siat->config->nit,
            fecha:             $fecha,
            codigoSucursal:    $this->siat->config->codigoSucursal,
            modalidad:         $this->siat->config->codigoModalidad,
            tipoEmision:       SiatConstants::EMISION_ONLINE,
            tipoFactura:       SiatConstants::FACTURA_CREDITO_FISCAL,
            tipoDocSector:     SiatConstants::SECTOR_EDUCATIVO,
            numeroFactura:     $numeroFacturaNormal,
            puntoVenta:        $this->siat->config->codigoPuntoVenta,
            codigoControlCufd: $cufdModel->codigoControl,
        );

        // 5. Construir XML
        $factura = $this->xmlBuilder->build(
            cabecera: [
                'nitEmisor'                    => $this->siat->config->nit,
                'razonSocialEmisor'            => config('siat.emisor.razonSocial'),
                'municipio'                    => config('siat.emisor.municipio'),
                'telefono'                     => config('siat.emisor.telefono'),
                'numeroFactura'                => $numeroFacturaNormal,
                'cuf'                          => $cufData['cuf'],
                'cufd'                         => $cufdModel->codigo,
                'codigoSucursal'               => $this->siat->config->codigoSucursal,
                'direccion'                    => $sucursalDireccion,
                'codigoPuntoVenta'             => $this->siat->config->codigoPuntoVenta,
                'fechaEmision'                 => $cufData['fechaEnvio'],
                'nombreRazonSocial'            => $request->razonSocial,
                'codigoTipoDocumentoIdentidad' => $request->codigoTipoDocumentoIdentidad,
                'numeroDocumento'              => $request->numeroDocumento,
                'complemento'                  => $request->complemento,
                'nombreEstudiante'             => $request->nombreEstudiante,
                'periodoFacturado'             => $request->periodoFacturado,
                'codigoMetodoPago'             => $request->codigoMetodoPago,
                'montoTotal'                   => $request->montoTotal,
                'montoTotalSujetoIva'          => $request->montoTotalSujetoIva,
                'codigoMoneda'                 => SiatConstants::MONEDA_BOLIVIANO,
                'tipoCambio'                   => SiatConstants::TIPO_CAMBIO_DEFAULT,
                'codigoActividad'              => $request->codigoActividad,
                'leyenda'                      => $leyenda->descripcionLeyenda ?? '',
                'usuario'                      => $usuarioActual,
                'codigoDocumentoSector'         => SiatConstants::SECTOR_EDUCATIVO,
            ],
            detalles: $request->detalles,
        );

        // 6. Comprimir y hashear
        $nombreArchivo = 'siat_factura_' . now()->format('YmdHis') . '_' . uniqid() . '.xml';
        $rutaRelativa  = 'siat/facturas/' . $nombreArchivo;
        Storage::put($rutaRelativa, $factura);

        $archivo     = gzencode($factura, 9);
        $hashArchivo = hash('sha256', $archivo);
        $cuis        = $this->ultimoCuis();

        // 7. Enviar factura al SIAT
        try {
            $facturaResponse = $this->siat->facturaEducativo->recepcionFactura(
                codigoAmbiente:        $this->siat->config->codigoAmbiente,
                codigoDocumentoSector:  SiatConstants::SECTOR_EDUCATIVO,
                codigoEmision:         SiatConstants::EMISION_ONLINE,
                codigoModalidad:       $this->siat->config->codigoModalidad,
                codigoPuntoVenta:      $this->siat->config->codigoPuntoVenta,
                codigoSistema:         $this->siat->config->codigoSistema,
                codigoSucursal:        $this->siat->config->codigoSucursal,
                cufd:                  $cufdModel->codigo,
                cuis:                  $cuis,
                nit:                   $this->siat->config->nit,
                tipoFacturaDocumento:  SiatConstants::FACTURA_CREDITO_FISCAL,
                archivo:               $archivo,
                fechaEnvio:            $cufData['fechaEnvio'],
                hashArchivo:           $hashArchivo,
            );
        } catch (\Throwable $e) {
            Log::error('Error al enviar factura al SIAT', [
                'error' => $e->getMessage(),
                'cuf'   => $cufData['cuf'],
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al comunicarse con el SIAT. Intente nuevamente.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 503);
        }

        // 8. Construir respuesta
        $url = $this->buildQrUrl($cufData['cuf'], $numeroFacturaNormal, $facturaResponse);

        $response = [
            'success'    => true,
            'message'    => 'Factura enviada al SIAT.',
            'data'       => [
                'cuf'           => $cufData['cuf'],
                'nroFactura'    => $numeroFacturaNormal,
                'url'           => $url,
                'siatResponse'  => $facturaResponse,
            ],
        ];

        // Incluir datos de debug solo en modo development
        if (config('app.debug')) {
            $response['debug'] = [
                'xml'             => $factura,
                'hash'            => $hashArchivo,
                'archivoTemporal' => $rutaRelativa,
                'cadenas'         => $cufData,
            ];
        }

        SiatFactura::create([
            'codigo_documento_sector' => SiatConstants::SECTOR_EDUCATIVO,
            'codigoDescripcion'       => $facturaResponse['data']->RespuestaServicioFacturacion->codigoDescripcion,
            'codigoRecepcion'         => $facturaResponse['data']->RespuestaServicioFacturacion?->codigoRecepcion ?? 'NO CODIGO',
            'cuf'                     => $cufData['cuf'],
            'cufd'                    => $cufdModel->codigo,
            'fecha_emision'           => $cufData['fechaEnvio'],
            'nit_cliente'             => $request->numeroDocumento,
            'razon_social_cliente'    => $request->razonSocial,
            'monto_total'             => $request->montoTotal,
            'documento_xml'           => $factura,
            'url'                     => $url,
        ]);

        return response()->json($response);
    }

    // ─── Métodos privados auxiliares ─────────────────────────────────

    /**
     * Obtiene una leyenda aleatoria del SIAT para incluir en la factura.
     */
    private function obtenerLeyendaAleatoria(): ?object
    {
        $cuis = $this->ultimoCuis();

        if ($cuis === null) {
            Log::warning('No hay CUIS disponible para obtener leyendas.');
            return null;
        }

        try {
            $leyendas = $this->siat->sincronizacion->sincronizarListaLeyendasFactura(
                codigoAmbiente:   $this->siat->config->codigoAmbiente,
                codigoPuntoVenta: $this->siat->config->codigoPuntoVenta,
                codigoSistema:    $this->siat->config->codigoSistema,
                codigoSucursal:   $this->siat->config->codigoSucursal,
                cuis:             $cuis,
                nit:              $this->siat->config->nit,
            );

            $lista = $leyendas['data']
                ?->RespuestaListaParametricasLeyendas
                ?->listaLeyendas
                ?? [];

            if (!empty($lista)) {
                return $lista[array_rand($lista)];
            }
        } catch (\Throwable $e) {
            Log::error('Error al obtener leyendas del SIAT', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Construye la URL QR de verificación en el portal del SIAT.
     */
    private function buildQrUrl(string $cuf, int $nroFactura, array $facturaResponse): string
    {
        $codigoRecepcion = $facturaResponse['data']
            ->RespuestaServicioFacturacion
            ->codigoRecepcion ?? null;

        if (!$codigoRecepcion) {
            return '';
        }

        $baseUrl = $this->siat->config->codigoAmbiente === 2
            ? 'https://pilotosiat.impuestos.gob.bo'
            : 'https://siat.impuestos.gob.bo';

        return "{$baseUrl}/consulta/QR?nit={$this->siat->config->nit}&cuf={$cuf}&numero={$nroFactura}";
    }
}