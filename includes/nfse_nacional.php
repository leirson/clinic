<?php
/**
 * Gateway semântico para emissão de NFSe Nacional.
 *
 * Mantém compatibilidade com a implementação SOAP já existente,
 * preservando a base técnica atual enquanto a plataforma passa a
 * operar com nomenclatura de NFSe Nacional no sistema.
 */
require_once __DIR__ . '/asten.php';

class NFSeNacionalGateway extends AstenNFSe {
}
