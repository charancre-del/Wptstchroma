<?php
namespace {
    if (!function_exists('home_url')) {
        function home_url() {
            return 'https://qa.invalid';
        }
    }

    if (!function_exists('wp_mail')) {
        function wp_mail($to, $subject, $message, $headers = '', $attachments = []) {
            return ($GLOBALS['cqa_test_mail_callback'])($to, $subject, $message, $headers, $attachments);
        }
    }

    if (!function_exists('esc_html')) {
        function esc_html($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
    }
}

namespace ChromaQA\Tests\Security {
    use ChromaQA\Notifications\Email_Notifications;
    use ChromaQA\Utils\Private_Temp_Storage;
    use PHPUnit\Framework\TestCase;

    class EmailAttachmentCleanupTest extends TestCase
    {
        private $root;
        private $original_environment;

        protected function setUp(): void
        {
            $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cqa-mail-test-' . bin2hex(random_bytes(8));
            $this->original_environment = getenv('CQA_PRIVATE_TEMP_DIR');
            putenv('CQA_PRIVATE_TEMP_DIR=' . $this->root);
        }

        protected function tearDown(): void
        {
            unset($GLOBALS['cqa_test_mail_callback']);
            if (is_dir($this->root)) {
                foreach (scandir($this->root) ?: [] as $entry) {
                    if ($entry !== '.' && $entry !== '..') {
                        unlink($this->root . DIRECTORY_SEPARATOR . $entry);
                    }
                }
                rmdir($this->root);
            }
            if ($this->original_environment === false) {
                putenv('CQA_PRIVATE_TEMP_DIR');
            } else {
                putenv('CQA_PRIVATE_TEMP_DIR=' . $this->original_environment);
            }
        }

        public function test_synchronous_mail_consumes_then_deletes_private_attachment()
        {
            $attachment = Private_Temp_Storage::write('synthetic pdf', 'pdf');
            $GLOBALS['cqa_test_mail_callback'] = function ($to, $subject, $message, $headers, $attachments) use ($attachment) {
                $this->assertSame([$attachment], $attachments);
                $this->assertFileExists($attachment);
                return true;
            };

            $method = new \ReflectionMethod(Email_Notifications::class, 'send_email');
            $method->setAccessible(true);
            $this->assertTrue($method->invoke(null, 'qa@example.invalid', 'Synthetic', 'Body', $attachment));
            $this->assertFileDoesNotExist($attachment);
        }

        public function test_mail_exception_still_deletes_private_attachment()
        {
            $attachment = Private_Temp_Storage::write('synthetic pdf', 'pdf');
            $GLOBALS['cqa_test_mail_callback'] = static function () {
                throw new \RuntimeException('synthetic mail failure');
            };

            $method = new \ReflectionMethod(Email_Notifications::class, 'send_email');
            $method->setAccessible(true);
            try {
                $method->invoke(null, 'qa@example.invalid', 'Synthetic', 'Body', $attachment);
                $this->fail('The synthetic mail failure was not propagated.');
            } catch (\RuntimeException $error) {
                $this->assertSame('synthetic mail failure', $error->getMessage());
            }
            $this->assertFileDoesNotExist($attachment);
        }
    }
}
