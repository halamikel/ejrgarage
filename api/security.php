<?php
/**
 * EJR Garage — Input Validation & Sanitization Library
 *
 * OWASP A03: Injection
 * OWASP A04: Insecure Design
 * ─────────────────────────────────────────────────────────────
 * Centralised schema-based validation so every endpoint validates
 * the same way. Call ejr_validate($data, $schema) and get back
 * a clean array or a 400 response.
 *
 * Schema format:
 *   'field' => ['type' => 'string|int|email|phone|password', 'required' => bool,
 *               'min' => int, 'max' => int, 'pattern' => '/regex/']
 */

/**
 * Validate and sanitize an input array against a schema.
 * On failure: emits HTTP 400 JSON and exits.
 * On success: returns the cleaned data array (only schema fields, no extras).
 *
 * OWASP: "reject unexpected fields" — we whitelist-only; anything
 * not in the schema is silently dropped.
 */
function ejr_validate(array $raw, array $schema): array {
    $clean  = [];
    $errors = [];

    foreach ($schema as $field => $rules) {
        $required = $rules['required'] ?? true;
        $value    = $raw[$field] ?? null;

        // Presence check
        if ($value === null || $value === '') {
            if ($required) {
                $errors[] = "'$field' is required.";
            }
            continue;
        }

        $type = $rules['type'] ?? 'string';

        switch ($type) {
            case 'int':
                if (!is_numeric($value) || (int)$value != $value) {
                    $errors[] = "'$field' must be an integer.";
                    continue 2;
                }
                $value = (int)$value;
                if (isset($rules['min']) && $value < $rules['min']) $errors[] = "'$field' must be at least {$rules['min']}.";
                if (isset($rules['max']) && $value > $rules['max']) $errors[] = "'$field' must be at most {$rules['max']}.";
                break;

            case 'email':
                $value = strtolower(trim((string)$value));
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "'$field' must be a valid email address.";
                    continue 2;
                }
                if (strlen($value) > ($rules['max'] ?? 254)) $errors[] = "'$field' is too long.";
                break;

            case 'phone':
                // Strip everything except digits
                $value = preg_replace('/\D/', '', (string)$value);
                // Accept 10-digit (9XXXXXXXXX), 11-digit (09XXXXXXXXX), or 12-digit (639XXXXXXXXX)
                if (!preg_match('/^(9\d{9}|09\d{9}|639\d{9})$/', $value)) {
                    $errors[] = "'$field' must be a valid Philippine mobile number.";
                    continue 2;
                }
                // Normalize to 639XXXXXXXXX
                if (strlen($value) === 10) $value = '63' . $value;
                if (strlen($value) === 11) $value = '63' . substr($value, 1);
                break;

            case 'password':
                $value = (string)$value;
                $min   = $rules['min'] ?? 8;
                $max   = $rules['max'] ?? 128;
                if (strlen($value) < $min) $errors[] = "Password must be at least $min characters.";
                if (strlen($value) > $max) $errors[] = "Password must be at most $max characters.";
                break;

            case 'string':
            default:
                $value = trim((string)$value);
                // Strip null bytes (OWASP: injection prevention)
                $value = str_replace("\0", '', $value);
                $min   = $rules['min'] ?? 0;
                $max   = $rules['max'] ?? 1000;
                if (strlen($value) < $min) $errors[] = "'$field' must be at least $min characters.";
                if (strlen($value) > $max) $errors[] = "'$field' is too long (max $max chars).";
                if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                    $errors[] = "'$field' contains invalid characters.";
                }
                break;
        }

        $clean[$field] = $value;
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
        exit;
    }

    return $clean;
}

/**
 * Parse JSON body and reject non-JSON or oversized payloads.
 * Max 32 KB — enough for any legitimate form, blocks oversized attack payloads.
 */
function ejr_json_input(int $maxBytes = 32768): array {
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if (strlen($raw) > $maxBytes) {
        http_response_code(413);
        echo json_encode(['status' => 'error', 'message' => 'Request payload too large.']);
        exit;
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON body.']);
        exit;
    }
    return $data;
}
