<?php

namespace AnimaID\Services;

use AnimaID\Config\ConfigManager;
use PDO;

/**
 * Audit Service
 * Persists structured audit records for mutating API actions.
 * All logging is gated by the features.audit_logging config flag.
 */
class AuditService
{
    private PDO $db;
    private ConfigManager $config;

    public function __construct(PDO $db, ConfigManager $config)
    {
        $this->db     = $db;
        $this->config = $config;
    }

    private function isEnabled(): bool
    {
        return $this->config->get('features.audit_logging') === true;
    }

    /**
     * Write one audit record.
     *
     * @param int|null $userId       Authenticated user, or null for anonymous.
     * @param string   $action       Short verb describing the operation (e.g. "create.children").
     * @param string   $resourceType Table / entity name being acted upon.
     * @param int|null $resourceId   Primary-key value of the affected row, if known.
     * @param array    $context      Arbitrary key/value pairs serialised as JSON.
     * @param string   $ipAddress    Remote IP extracted from the request.
     * @param string   $userAgent    User-Agent header value.
     */
    public function log(
        ?int $userId,
        string $action,
        string $resourceType = '',
        ?int $resourceId = null,
        array $context = [],
        string $ipAddress = '',
        string $userAgent = ''
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO audit_log
                (user_id, action, resource_type, resource_id, ip_address, user_agent, request_data)
            VALUES
                (:user_id, :action, :resource_type, :resource_id, :ip_address, :user_agent, :request_data)
        ");

        $stmt->execute([
            ':user_id'       => $userId,
            ':action'        => $action,
            ':resource_type' => $resourceType ?: null,
            ':resource_id'   => $resourceId,
            ':ip_address'    => $ipAddress ?: null,
            ':user_agent'    => $userAgent ?: null,
            ':request_data'  => empty($context) ? null : json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
