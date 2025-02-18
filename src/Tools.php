<?php

namespace NFContador\Common;

use Exception;

/**
 * Classe Tools
 *
 * Classe responsável pela comunicação com a API NFContador
 *
 * @category  NFContador
 * @package   NFContador\Common\Tools
 * @author    Diego Almeida <diego.feres82 at gmail dot com>
 * @copyright 2020 NFSERVICE
 * @license   https://opensource.org/licenses/MIT MIT
 */
class Tools
{
    /**
     * URL base para comunicação com a API
     *
     * @var string
     */
    public static $API_URL = [
        1 => 'https://api.fuganholi-contabil.com.br/api',
        2 => 'http://api.nfcontador.com.br/api',
        3 => 'https://api.sandbox.fuganholi-contabil.com.br/api',
        4 => 'https://api.dusk.fuganholi-contabil.com.br/api'
    ];

    /**
     * Variável responsável por armazenar os dados a serem utilizados para comunicação com a API
     * Dados como token, ambiente(produção ou homologação) e debug(true|false)
     *
     * @var array
     */
    private $config = [
        'token' => '',
        'company-cnpj' => '',
        'environment' => '',
        'debug' => false,
        'upload' => false,
        'decode' => true
    ];

    /**
     * Define se a classe realizará um upload
     *
     * @param bool $isUpload Boleano para definir se é upload ou não
     *
     * @access public
     * @return void
     */
    public function setUpload(bool $isUpload) :void
    {
        $this->config['upload'] = $isUpload;
    }

    /**
     * Define se a classe realizará o decode do retorno
     *
     * @param bool $decode Boleano para definir se fa decode ou não
     *
     * @access public
     * @return void
     */
    public function setDecode(bool $decode) :void
    {
        $this->config['decode'] = $decode;
    }

    /**
     * Função responsável por definir se está em modo de debug ou não a comunicação com a API
     * Utilizado para pegar informações da requisição
     *
     * @param bool $isDebug Boleano para definir se é produção ou não
     *
     * @access public
     * @return void
     */
    public function setDebug(bool $isDebug) :void
    {
        $this->config['debug'] = $isDebug;
    }

    /**
     * Função responsável por definir o token a ser utilizado para comunicação com a API
     *
     * @param string $token Token para autenticação na API
     *
     * @access public
     * @return void
     */
    public function setToken(string $token) :void
    {
        $this->config['token'] = $token;
    }

    /**
     * Função responsável por definir o cnpj a ser utilizado para comunicação com a API
     *
     * @param string $cnpj CNPJ para autenticação na API
     *
     * @access public
     * @return void
     */
    public function setCnpj(string $cnpj): void
    {
        $this->config['company-cnpj'] = $cnpj;
    }

    /**
     * Função responsável por setar o ambiente utilizado na API
     *
     * @param int $environment Ambiente API (1 - Produção | 2 - Local | 3 - Sandbox | 4 - Dusk)
     *
     * @access public
     * @return void
     */
    public function setEnvironment(int $environment) :void
    {
        if (in_array($environment, [1, 2, 3, 4])) {
            $this->config['environment'] = $environment;
        }
    }

    /**
     * Recupera se é upload ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getUpload() : bool
    {
        return $this->config['upload'];
    }

    /**
     * Recupera se faz decode ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getDecode() : bool
    {
        return $this->config['decode'];
    }

    /**
     * Recupera o CNPJ setado para comunicação com a API
     *
     *
     * @access public
     * @return string
     */
    public function getCnpj() : string
    {
        return $this->config['company-cnpj'];
    }

    /**
     * Recupera o ambiente setado para comunicação com a API
     *
     * @access public
     * @return int
     */
    public function getEnvironment() :int
    {
        return $this->config['environment'];
    }

    /**
     * Retorna os cabeçalhos padrão para comunicação com a API
     *
     * @access private
     * @return array
     */
    private function getDefaultHeaders() :array
    {
        $headers = [
            'access-token: '.$this->config['token'],
            'company-cnpj: '.$this->config['company-cnpj'],
            'Accept: application/json',
        ];

        if (!$this->config['upload']) {
            $headers[] = 'Content-Type: application/json';
        } else {
            $headers[] = 'Content-Type: multipart/form-data';
        }
        return $headers;
    }

    /**
     * Consulta uma empresa no NFContador
     */
    public function consultaEmpresa(string $cnpj = '', array $params = []): array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'cnpj_company';
            }, ARRAY_FILTER_USE_BOTH);

            if (!empty($cnpj)) {
                $params[] = [
                    'name' => 'cnpj_company',
                    'value' => $cnpj
                ];
            }

            $response = $this->get('/systems/companies', $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Cadastra uma empresa nova no NFContador
     */
    public function cadastraEmpresa(array $dados, array $params = []): array
    {
        $errors = [];
        if (!isset($dados['cpfcnpj']) || empty($dados['cpfcnpj'])) {
            $errors[] = 'É obrigatório o envio do CPF/CNPJ da empresa';
        }
        if (!isset($dados['type']) || empty($dados['type'])) {
            $dados['type'] = 2;
        }
        if (!isset($dados['name']) || empty($dados['name'])) {
            $errors[] = 'O Nome da empresa é obrigatório';
        }
        if (!isset($dados['email']) || empty($dados['email'])) {
            $errors[] = 'O E-mail da empresa é obrigatório';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $response = $this->post('systems/companies', $dados, $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (Exception $error) {
            throw $error;
        }
    }

    /**
     * Atualiza uma empresa no NFContador
     */
    public function atualizaEmpresa(int $id, array $dados, array $params = []): array
    {
        $errors = [];
        if (isset($dados['cpfcnpj']) && empty($dados['cpfcnpj'])) {
            $errors[] = 'O CPF/CNPJ da empresa não pode ficar vazio';
        }
        if (isset($dados['type']) && empty($dados['type'])) {
            $dados['type'] = 2;
        }
        if (isset($dados['name']) && empty($dados['name'])) {
            $errors[] = 'O Nome da empresa não pode ficar vazio';
        }
        if (isset($dados['email']) && empty($dados['email'])) {
            $errors[] = 'O E-mail da empresa não pode ficar vazio';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $response = $this->put('systems/companies/'.$id, $dados, $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Deleta uma empresa
     */
    public function deletaEmpresa(int $id, array $params = []): array
    {
        if (!isset($id) || empty($id)) {
            throw new Exception("O ID NFContador da empresa é obrigatório para exclusão", 1);
        }

        try {
            $response = $this->delete('systems/companies/'.$id, $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Sincroniza uma empresa como cliente do um contador
     */
    public function sincronizaCliente(array $dados, array $params = []): array
    {
        $errors = [];
        if (!isset($dados['cpfcnpj']) || empty($dados['cpfcnpj'])) {
            $errors[] = 'Informe o CPF/CNPJ do cliente';
        }
        if (!isset($dados['name']) || empty($dados['name'])) {
            $errors[] = 'Informe o Nome/Razão Social do cliente';
        }
        if (!isset($dados['email']) || empty($dados['email'])) {
            $errors[] = 'Informe o E-mail Social do cliente';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $response = $this->post('customers/sync', $dados, $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
    * Remove Sincronização de uma empresa como cliente de um contador
    */
    public function removeSincronizacaoCliente(string $cpfcnpj, array $params = []): array
    {
        $errors = [];
        if (!isset($cpfcnpj) || empty($cpfcnpj)) {
            $errors[] = 'Informe o CPF/CNPJ do cliente';
        }

        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $response = $this->post('customers/removesync', [$cpfcnpj], $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Lista os documentos de um cliente
     */
    public function listaDocumentos(array $params): array
    {
        try {
            $response = $this->get('customers/documents', $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (\Throwable $th) {
            throw new Exception($th, 1);
        }
    }

    /**
     * Envia um documento para o NFContador
     */
    public function enviaDocumento(array $dados, array $params = []): array
    {
        $errors = [];
        if (!isset($dados['person_id']) || empty($dados['person_id'])) {
            $errors[] = 'Informe a ID do cliente no NFContador';
        }
        if (!isset($dados['type']) || empty($dados['type'])) {
            $errors[] = 'Informe o tipo de documento sendo enviado';
        } else if (!in_array((int)$dados['type'], [1, 2, 3, 4, 5, 6])) {
            $errors[] = 'Informe um tipo de documento válido';
        }
        if (!isset($dados['document']) || empty($dados['document'])) {
            $errors[] = 'Informe o documento a ser enviado';
        }

        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $response = $this->post('customers/documents', $dados, $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (\Throwable $th) {
            throw new Exception($th, 1);
        }
    }

    /**
     * Função responsável por visualizar um documento
     *
     * @param int $document_id ID do documento
     * @param int $type tipo de acesso 1 - Download / 2 - Visualização
     * @return \stdClass
     */
    public function buscaDocumento(int $document_id, int $type = 1, array $params = []) :array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'type';
            }, ARRAY_FILTER_USE_BOTH);

            if (!empty($type)) {
                $params[] = [
                    'name' => 'type',
                    'value' => $type
                ];
            }

            $response = $this->get("customers/documents/$document_id", $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (\Exception $error) {
            throw $error;
        }
    }

    /**
     * Audita um usuário solicitante de um documento no NFContador
     *
     * @param array $dados dados do usuário a ser auditado no documento
     * @param int $document_id ID do documento a ser auditado
     * @return \stdClass
     */
    public function auditaUsuarioSolicitanteDocumento(array $dados, int $document_id, array $params = []): array
    {
        $errors = [];
        if (!isset($dados['name']) || empty($dados['name'])) {
            $errors[] = 'É obrigatório o envio do nome do solicitante do documento';
        }
        if (!isset($dados['user_id']) || empty($dados['user_id'])) {
            $errors[] = 'O id do usuário solicitante do documento é obrigatório';
        }
        if (!isset($document_id) || empty($document_id)) {
            $errors[] = 'O id do documento a ser requisitado é obrigatório';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $response = $this->post("/customers/documents/$document_id/audit", $dados, $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (Exception $error) {
            throw $error;
        }
    }


    /**
     * Função responsável por atualizar a resposta de sincronização de um cliente Contador
     *
     * @param array $dados Dados a serem enviados para a atualização
     *
     * @access public
     * @return object
     */
    public function atualizaRespostaSincronizacao(array $dados, array $params = [])
    {
        $errors = [];
        if (!isset($dados['sync']) || ($dados['sync'] !== true && $dados['sync'] !== false)) {
            $errors[] = 'A resposta deve ser de forma booleana';
        }
        if (!isset($dados['cpfcnpj']) || empty($dados['cpfcnpj'])) {
            $errors[] = 'Informe o CPF/CNPJ do cliente';
        }

        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $response = $this->post('systems/response/sync', $dados, $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (\Throwable $th) {
            throw new Exception($th, 1);
        }
    }

    /**
     * Função responsável por enviar solicitação de emissão de nfe para o bpo contabil
     *
     * @param  int  $order_id
     * @return \Illuminate\Http\Response
     */
    public function enviaEmissaoNfe(array $dados, int $order_id, array $params = []): array
    {
        $errors = [];
        if (!isset($order_id) || empty($order_id)) {
            $errors[] = 'É obrigatório o envio do order_id ou service_order_id';
        }
        if (!isset($dados['person_id']) || empty($dados['person_id'])) {
            $errors[] = 'Informe a ID do cliente no NFContador';
        }
        if (!isset($dados['emit_installments']) && !empty($dados['emit_installments'])) {
            $errors[] = 'Informe se irá emitir boletos';
        }
        if (!isset($dados['integration_id']) || empty($dados['integration_id'])) {
            $errors[] = 'Informe o ID da integração';
        }
        if (!isset($dados['cpfcnpj']) || empty($dados['cpfcnpj'])) {
            $errors[] = 'Informe o CPF/CNPJ do contador';
        }

        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $response = $this->post("/customers/nfes/$order_id/emission", $dados, $params);

            if ($response['httpCode'] === 200) {
                return $response;
            }

            if (isset($response['body']->errors) && !empty($response['body']->errors)) {
                throw new \Exception("\r\n".implode("\r\n", $response['body']->errors));
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (Exception $error) {
            throw $error;
        }
    }

    /**
     * Execute a GET Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function get(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a POST Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function post(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => !$this->config['upload'] ? json_encode($body) : $body,
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PUT Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function put(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($body)
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a DELETE Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function delete(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "DELETE"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a OPTION Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function options(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_CUSTOMREQUEST => "OPTIONS"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Função responsável por realizar a requisição e devolver os dados
     *
     * @param string $path Rota a ser acessada
     * @param array $opts Opções do CURL
     * @param array $params Parametros query a serem passados para requisição
     *
     * @access protected
     * @return array
     */
    protected function execute(string $path, array $opts = [], array $params = []) :array
    {
        if (!preg_match("/^\//", $path)) {
            $path = '/' . $path;
        }

        $url = self::$API_URL[$this->config['environment']].$path;

        $curlC = curl_init();

        if (!empty($opts)) {
            curl_setopt_array($curlC, $opts);
        }

        if (!empty($params)) {
            $paramsJoined = [];

            foreach ($params as $param) {
                if (isset($param['name']) && !empty($param['name']) && isset($param['value']) && !empty($param['value'])) {
                    $paramsJoined[] = urlencode($param['name'])."=".urlencode($param['value']);
                }
            }

            if (!empty($paramsJoined)) {
                $params = '?'.implode('&', $paramsJoined);
                $url = $url.$params;
            }
        }

        curl_setopt($curlC, CURLOPT_URL, $url);
        curl_setopt($curlC, CURLOPT_RETURNTRANSFER, true);
        if (!empty($dados)) {
            curl_setopt($curlC, CURLOPT_POSTFIELDS, json_encode($dados));
        }
        $retorno = curl_exec($curlC);
        $info = curl_getinfo($curlC);
        $return["body"] = ($this->config['decode'] || !$this->config['decode'] && $info['http_code'] != '200') ? json_decode($retorno) : $retorno;
        $return["httpCode"] = curl_getinfo($curlC, CURLINFO_HTTP_CODE);
        if ($this->config['debug']) {
            $return['info'] = curl_getinfo($curlC);
        }
        curl_close($curlC);

        return $return;
    }
}
