<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class VerifyCommand extends Command
{
    protected $signature = 'crm:verify';

    protected $description = 'CRM 系统部署验收验证';

    protected array $results = [];

    protected int $passed = 0;

    protected int $failed = 0;

    public function handle(): int
    {
        $this->info('========================================');
        $this->info('  CRM 系统部署验收验证');
        $this->info('========================================');
        $this->newLine();

        $checks = [
            '环境配置检查' => 'checkEnvironment',
            '数据库连接测试' => 'checkDatabase',
            '迁移完整性检查' => 'checkMigrations',
            '权限角色数据' => 'checkPermissions',
            '默认账号可用性' => 'checkDefaultUsers',
            '队列连接状态' => 'checkQueue',
            '缓存驱动状态' => 'checkCache',
            '目录权限检查' => 'checkDirectories',
        ];

        foreach ($checks as $name => $method) {
            $this->check($name, $method);
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('  验证结果汇总');
        $this->info('========================================');
        $this->info("通过: {$this->passed} / " . count($checks));
        $this->info("失败: {$this->failed} / " . count($checks));

        if ($this->failed > 0) {
            $this->error('存在验证失败项，请检查后重试。');

            return self::FAILURE;
        }

        $this->info('所有验证通过！系统部署成功。');

        return self::SUCCESS;
    }

    protected function check(string $name, string $method): void
    {
        try {
            $result = $this->$method();
            if ($result['status']) {
                $this->passed++;
                $this->line("  ✅ {$name} - <info>{$result['message']}</info>");
            } else {
                $this->failed++;
                $this->line("  ❌ {$name} - <error>{$result['message']}</error>");
            }
        } catch (\Exception $e) {
            $this->failed++;
            $this->line("  ❌ {$name} - <error>{$e->getMessage()}</error>");
        }
    }

    protected function checkEnvironment(): array
    {
        $appKey = config('app.key');
        $appEnv = config('app.env');
        $appDebug = config('app.debug');

        if (empty($appKey)) {
            return ['status' => false, 'message' => 'APP_KEY 未设置'];
        }

        $message = "环境: {$appEnv}" . ($appDebug ? ' (调试模式)' : '');

        return ['status' => true, 'message' => $message];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $database = config('database.connections.' . config('database.default') . '.database');

            return ['status' => true, 'message' => "连接正常 ({$database})"];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => '连接失败: ' . $e->getMessage()];
        }
    }

    protected function checkMigrations(): array
    {
        try {
            if (!Schema::hasTable('migrations')) {
                return ['status' => false, 'message' => 'migrations 表不存在'];
            }

            $migrationFiles = glob(database_path('migrations') . '/*.php');
            $executedMigrations = DB::table('migrations')->pluck('migration')->toArray();

            $pending = [];
            foreach ($migrationFiles as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if (!in_array($name, $executedMigrations)) {
                    $pending[] = $name;
                }
            }

            if (count($pending) > 0) {
                return ['status' => false, 'message' => '待执行迁移: ' . count($pending) . ' 个'];
            }

            return ['status' => true, 'message' => '所有迁移已执行 (' . count($migrationFiles) . ' 个)'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    protected function checkPermissions(): array
    {
        try {
            $roleCount = Role::count();
            $permissionCount = Permission::count();

            if ($roleCount === 0) {
                return ['status' => false, 'message' => '角色数据为空'];
            }

            if ($permissionCount === 0) {
                return ['status' => false, 'message' => '权限数据为空'];
            }

            return ['status' => true, 'message' => "角色: {$roleCount} 个, 权限: {$permissionCount} 个"];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    protected function checkDefaultUsers(): array
    {
        try {
            $userModel = config('auth.providers.users.model');
            $userCount = $userModel::count();

            $admin = $userModel::where('email', 'admin@shearerline.com')->first();

            if (!$admin) {
                return ['status' => false, 'message' => '默认管理员账号不存在'];
            }

            $roleCount = $admin->roles()->count();

            return ['status' => true, 'message' => "用户: {$userCount} 个, 管理员角色: {$roleCount} 个"];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    protected function checkQueue(): array
    {
        $connection = config('queue.default');

        try {
            if ($connection === 'sync') {
                return ['status' => true, 'message' => '同步模式 (开发环境)'];
            }

            if ($connection === 'database') {
                if (!Schema::hasTable('jobs')) {
                    return ['status' => false, 'message' => 'jobs 表不存在'];
                }

                $failedTable = config('queue.failed.table', 'failed_jobs');
                if (!Schema::hasTable($failedTable)) {
                    return ['status' => false, 'message' => "{$failedTable} 表不存在"];
                }

                return ['status' => true, 'message' => '数据库队列就绪'];
            }

            if ($connection === 'redis') {
                Cache::store('redis')->get('crm_queue_test');

                return ['status' => true, 'message' => 'Redis 队列就绪'];
            }

            return ['status' => true, 'message' => "队列驱动: {$connection}"];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    protected function checkCache(): array
    {
        $driver = config('cache.default');

        try {
            Cache::put('crm_verify_test', 'ok', 60);
            $value = Cache::get('crm_verify_test');
            Cache::forget('crm_verify_test');

            if ($value !== 'ok') {
                return ['status' => false, 'message' => '缓存读写测试失败'];
            }

            return ['status' => true, 'message' => "缓存驱动: {$driver}, 读写正常"];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    protected function checkDirectories(): array
    {
        $dirs = [
            storage_path('app'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            bootstrap_path('cache'),
        ];

        $failed = [];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                $failed[] = basename($dir) . ' (不存在)';
            } elseif (!is_writable($dir)) {
                $failed[] = basename($dir) . ' (不可写)';
            }
        }

        if (count($failed) > 0) {
            return ['status' => false, 'message' => '问题目录: ' . implode(', ', $failed)];
        }

        return ['status' => true, 'message' => '所有目录权限正常'];
    }
}
