<?php
/**
 * Native website forms (used when no GoHighLevel embed is configured, and as
 * a reliable fallback). Submissions are stored in the dashboard inbox, emailed
 * to the notification address and optionally forwarded to a GHL webhook.
 */
final class Forms
{
    public const TYPES = [
        'appointment' => 'Appointment Request',
        'contact' => 'Contact Message',
        'benefits' => 'Benefits Check',
    ];

    public static function token(): string
    {
        $t = (string)time();
        return $t . '.' . substr(sign('form' . $t), 0, 24);
    }

    public static function validToken(string $token): bool
    {
        [$t, $sig] = array_pad(explode('.', $token, 2), 2, '');
        if (!ctype_digit($t) || !hash_equals(substr(sign('form' . $t), 0, 24), $sig)) {
            return false;
        }
        $age = time() - (int)$t;
        return $age >= 2 && $age <= 86400;
    }

    public static function concerns(): array
    {
        return ['Neck pain', 'Back pain', 'Shoulder pain', 'Hip pain', 'Knee pain', 'Elbow, wrist or hand pain', 'Leg, ankle or foot pain', 'General joint pain', 'Headaches', 'Car accident injury', 'Work injury', 'Sports injury', 'Not sure / other'];
    }

    public static function fields(string $type): array
    {
        $locations = array_map(fn($l) => $l['name'], Content::locations());
        $locations[] = 'No preference';
        $common = [
            'first_name' => ['label' => 'First name', 'type' => 'text', 'required' => true, 'autocomplete' => 'given-name', 'half' => true],
            'last_name' => ['label' => 'Last name', 'type' => 'text', 'required' => true, 'autocomplete' => 'family-name', 'half' => true],
            'phone' => ['label' => 'Phone', 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'half' => true],
            'email' => ['label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'half' => true],
        ];
        $fields = match ($type) {
            'appointment' => $common + [
                'location' => ['label' => 'Preferred location', 'type' => 'select', 'options' => $locations, 'required' => true, 'half' => true],
                'patient_status' => ['label' => 'New or current patient', 'type' => 'select', 'options' => ['New patient', 'Current patient'], 'required' => true, 'half' => true],
                'concern' => ['label' => 'Main concern', 'type' => 'select', 'options' => self::concerns(), 'required' => true, 'half' => true],
                'insurance' => ['label' => 'Insurance (carrier & plan)', 'type' => 'text', 'placeholder' => 'Optional', 'half' => true],
                'preferred_day' => ['label' => 'Preferred day', 'type' => 'select', 'options' => ['First available', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], 'half' => true],
                'preferred_time' => ['label' => 'Preferred time', 'type' => 'select', 'options' => ['Any time', 'Morning', 'Midday', 'Afternoon'], 'half' => true],
                'message' => ['label' => 'Anything else we should know?', 'type' => 'textarea', 'placeholder' => 'Briefly, how can we help? Please don\'t include detailed medical information.'],
            ],
            'benefits' => $common + [
                'insurance' => ['label' => 'Insurance carrier', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Blue Cross, Aetna', 'half' => true],
                'location' => ['label' => 'Preferred location', 'type' => 'select', 'options' => $locations, 'half' => true],
                'message' => ['label' => 'What would you like to know?', 'type' => 'textarea', 'placeholder' => 'Optional'],
            ],
            default => [
                'first_name' => $common['first_name'], 'last_name' => $common['last_name'],
                'phone' => ['label' => 'Phone', 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'half' => true],
                'email' => ['label' => 'Email', 'type' => 'email', 'autocomplete' => 'email', 'half' => true],
                'location' => ['label' => 'Location', 'type' => 'select', 'options' => $locations],
                'message' => ['label' => 'Message', 'type' => 'textarea', 'required' => true, 'placeholder' => 'How can we help? Please don\'t include detailed medical information.'],
            ],
        };
        // A location choice only makes sense once offices are added in the dashboard
        if (count($locations) < 2) {
            unset($fields['location']);
        }
        return $fields;
    }

    public static function handle(): void
    {
        $isAjax = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        $fail = function (string $msg, int $code = 422) use ($isAjax) {
            if ($isAjax) {
                json_out(['ok' => false, 'error' => $msg], $code);
            }
            http_response_code($code);
            echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width"><body style="font-family:system-ui;max-width:560px;margin:4rem auto;padding:0 1rem"><h1>We couldn\'t send your request</h1><p>' . e($msg) . '</p><p><a href="javascript:history.back()">Go back</a> or call <a href="' . e(tel_href(setting('phone'))) . '">' . e(setting('phone')) . '</a>.</p>';
            exit;
        };
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $fail('Invalid request.', 405);
        }
        $type = (string)($_POST['_form'] ?? 'contact');
        if (!isset(self::TYPES[$type])) {
            $fail('Unknown form.');
        }
        // Spam protection: honeypot + signed timestamp + rate limit
        if (!empty($_POST['website']) || !self::validToken((string)($_POST['_token'] ?? ''))) {
            $fail('Your session expired. Please refresh the page and try again.');
        }
        $since = date('Y-m-d H:i:s', time() - 600);
        if ((int)DB::val('SELECT COUNT(*) FROM submissions WHERE ip = ? AND created_at > ?', [client_ip(), $since]) >= 5) {
            $fail('Too many requests. Please call us instead.', 429);
        }
        $data = [];
        foreach (self::fields($type) as $name => $f) {
            $v = trim((string)($_POST[$name] ?? ''));
            $v = mb_substr(strip_tags($v), 0, $f['type'] === 'textarea' ? 3000 : 200);
            if (!empty($f['required']) && $v === '') {
                $fail('Please complete the "' . $f['label'] . '" field.');
            }
            if ($f['type'] === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $fail('Please enter a valid email address.');
            }
            if ($f['type'] === 'tel' && $v !== '' && strlen(digits($v)) < 10) {
                $fail('Please enter a valid phone number.');
            }
            if ($f['type'] === 'select' && $v !== '' && !in_array($v, $f['options'], true)) {
                $v = '';
            }
            $data[$name] = $v;
        }
        if (empty($_POST['consent'])) {
            $fail('Please confirm we may contact you about your request.');
        }
        $data['consent'] = 'Yes';
        $page = mb_substr((string)($_POST['_page'] ?? ''), 0, 250);
        $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        $id = DB::insert('submissions', [
            'form' => $type,
            'name' => $name,
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'page' => $page,
            'ip' => client_ip(),
            'status' => 'new',
            'created_at' => now(),
        ]);
        $forwarded = self::forward($type, $data, $page);
        if ($forwarded) {
            DB::update('submissions', ['forwarded' => 1], 'id = ?', [$id]);
        }
        self::notify($type, $data, $page);

        $msg = $type === 'appointment'
            ? 'Thank you! Your request has been received. Our team will contact you to confirm your appointment time.'
            : 'Thank you! Your message has been received. Our team will be in touch shortly.';
        if ($isAjax) {
            json_out(['ok' => true, 'message' => $msg]);
        }
        $back = $page && str_starts_with($page, '/') ? $page : url('');
        redirect($back . (str_contains($back, '?') ? '&' : '?') . 'sent=1#form');
    }

    private static function forward(string $type, array $data, string $page): bool
    {
        $hook = trim((string)setting('ghl_webhook_url'));
        if ($hook === '' || !preg_match('~^https://~', $hook) || !function_exists('curl_init')) {
            return false;
        }
        $payload = array_merge($data, [
            'form' => $type,
            'form_name' => self::TYPES[$type],
            'full_name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'source' => 'Website',
            'page' => abs_url($page ?: '/'),
            'submitted_at' => date('c'),
        ]);
        $ch = curl_init($hook);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    private static function notify(string $type, array $data, string $page): void
    {
        $to = trim((string)setting('notify_email'));
        if ($to === '' || !function_exists('mail')) {
            return;
        }
        $lines = [];
        foreach ($data as $k => $v) {
            $lines[] = ucwords(str_replace('_', ' ', $k)) . ': ' . $v;
        }
        $lines[] = 'Page: ' . abs_url($page ?: '/');
        $lines[] = '';
        $lines[] = 'View in dashboard: ' . abs_url('admin/#/submissions');
        $host = preg_replace('/^www\./', '', parse_url(site_origin(), PHP_URL_HOST) ?: 'localhost');
        $headers = [
            'From: ' . setting('site_short_name') . ' Website <noreply@' . $host . '>',
            'Content-Type: text/plain; charset=UTF-8',
        ];
        if (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $data['email'];
        }
        @mail($to, 'New ' . self::TYPES[$type] . ' — ' . setting('site_short_name'), implode("\n", $lines), implode("\r\n", $headers));
    }
}
