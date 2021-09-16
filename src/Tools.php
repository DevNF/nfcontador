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
    public static $API_URL = 'http://api.nfcontador-sandbox.nfservice.com.br/api/systems';

    /**
     * Variável responsável por armazenar os dados a serem utilizados para comunicação com a API
     * Dados como token, ambiente(produção ou homologação) e debug(true|false)
     *
     * @var array
     */
    private $config = [
        'token' => '',
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
     * Retorna os cabeçalhos padrão para comunicação com a API
     *
     * @access private
     * @return array
     */
    private function getDefaultHeaders() :array
    {
        $headers = [
            'Authorization: Bearer '.$this->config['token'],
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

            $dados = $this->get('/companies', $params);

            if (!isset($dados['body']->message)) {
                return $dados;
            }

            throw new Exception($dados['body']->message, 1);
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
            $dados = $this->post('companies', $dados, $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            foreach ($dados['body']->errors as $key => $error) {
                if (strpos($key, 'position') !== false) {
                    $errors[] = implode('; ', $error);
                } else {
                    $errors[] = $error;
                }
            }

            throw new Exception("\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw $error;
        }
    }

    /**
     * Atualiza uma empresa nova no NFContador
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
            $dados = $this->put('companies/'.$id, $dados, $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            foreach ($dados['body']->errors as $key => $error) {
                if (strpos($key, 'position') !== false) {
                    $errors[] = implode('; ', $error);
                } else {
                    $errors[] = $error;
                }
            }

            throw new Exception("\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Deleta uma empresa
     */
    public function deletaEmpresa(int $id, array $params = [])
    {
        if (!isset($id) || empty($id)) {
            throw new Exception("O ID NFContador da empresa é obrigatório para exclusão", 1);
        }

        try {
            $dados = $this->delete('companies/'.$id, $params);

            if ($dados['httpCode'] == 200) {
                return 'Empresa deletada com sucesso';
            }

            foreach ($dados['body']->errors as $key => $error) {
                if (strpos($key, 'position') !== false) {
                    $errors[] = implode('; ', $error);
                } else {
                    $errors[] = $error;
                }
            }

            throw new Exception("\r\n".implode("\r\n", $errors), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
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

        $url = self::$API_URL.$path;

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
