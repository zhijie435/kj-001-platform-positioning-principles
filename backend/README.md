# CRM 客户跟进系统 - 后端服务

> 平台定位核心原则版本

## 项目简介

基于 Laravel 13 构建的多角色 CRM 客户跟进系统，支持平台、供应商、分销商、区域代理等多种用户类型，提供订单管理、支付结算、库存管理、跨境报关等核心业务功能。

## 系统要求

- PHP >= 8.3
- Composer >= 2.7
- Node.js >= 18.x
- NPM >= 9.x
- SQLite / MySQL 5.7+ / PostgreSQL 13+
- 可选：Redis（缓存和队列）

## 快速开始

### 1. 环境配置

复制环境变量配置文件：

```bash
cp .env.example .env
```

生成应用密钥：

```bash
php artisan key:generate
```

### 2. 安装依赖

```bash
composer install
npm install
```

### 3. 数据库迁移与种子数据

```bash
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder
```

### 4. 构建前端资源

```bash
npm run build
```

### 5. 启动服务

```bash
php artisan serve
```

访问 `http://localhost:8000` 查看应用。

---

## 环境变量详解

### 应用基础配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `APP_NAME` | `CRM` | 应用名称 |
| `APP_ENV` | `local` | 运行环境：`local` / `staging` / `production` |
| `APP_KEY` | - | 应用加密密钥，使用 `php artisan key:generate` 生成 |
| `APP_DEBUG` | `true` | 调试模式，生产环境必须设为 `false` |
| `APP_URL` | `http://localhost:8000` | 应用访问地址 |
| `APP_LOCALE` | `zh_CN` | 应用默认语言 |
| `APP_TIMEZONE` | `Asia/Shanghai` | 时区设置 |

### 数据库配置

#### SQLite（默认/开发环境）

```env
DB_CONNECTION=sqlite
```

#### MySQL（生产环境推荐）

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crm
DB_USERNAME=root
DB_PASSWORD=secret
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

### 认证与安全

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `AUTH_GUARD` | `web` | 默认认证守卫 |
| `AUTH_PASSWORD_BROKER` | `users` | 密码重置代理 |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost,localhost:3000,...` | SPA 认证的有状态域名 |
| `BCRYPT_ROUNDS` | `12` | 密码加密轮数 |

### 队列配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `QUEUE_CONNECTION` | `database` | 队列驱动：`sync` / `database` / `redis` |
| `DB_QUEUE_TABLE` | `jobs` | 队列表名 |
| `DB_QUEUE` | `default` | 默认队列名称 |
| `DB_QUEUE_RETRY_AFTER` | `90` | 任务重试间隔（秒） |
| `QUEUE_FAILED_DRIVER` | `database-uuids` | 失败任务存储驱动 |

### 缓存配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `CACHE_STORE` | `database` | 缓存驱动：`database` / `redis` / `file` |
| `PERMISSION_CACHE_EXPIRATION` | `86400` | 权限缓存有效期（秒） |
| `CUSTOMER_GROUPS_CACHE_EXPIRATION` | `86400` | 客户分组缓存有效期（秒） |

### Redis 配置（可选）

```env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### 邮件配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `MAIL_MAILER` | `log` | 邮件驱动：`log` / `smtp` / `ses` |
| `MAIL_HOST` | `127.0.0.1` | SMTP 服务器地址 |
| `MAIL_PORT` | `2525` | SMTP 端口 |
| `MAIL_FROM_ADDRESS` | `noreply@crm.example.com` | 发件人地址 |
| `MAIL_FROM_NAME` | `${APP_NAME}` | 发件人名称 |

---

## 队列任务

### 队列驱动

系统默认使用 `database` 队列驱动，支持以下驱动切换：

- **sync**：同步执行，适合开发调试
- **database**：数据库队列，适合中小规模
- **redis**：Redis 队列，适合高并发生产环境

### 启动队列 Worker

```bash
# 启动单个队列 worker
php artisan queue:work

# 指定队列和超时时间
php artisan queue:work --queue=default --timeout=60 --tries=3

# 监听模式（开发环境，自动加载代码变更）
php artisan queue:listen --tries=1 --timeout=0
```

### 队列任务列表

| 任务类型 | 说明 | 队列 |
|----------|------|------|
| 订单状态变更通知 | 订单状态变更后异步发送通知 | `default` |
| 支付结算处理 | 支付对账和结算处理 | `default` |
| 数据报表生成 | 异步生成销售报表 | `reports` |

### 失败任务处理

```bash
# 查看失败任务
php artisan queue:failed

# 重试所有失败任务
php artisan queue:retry all

# 重试指定 ID 的失败任务
php artisan queue:retry 1

# 清空所有失败任务
php artisan queue:flush
```

### Supervisor 配置（生产环境）

```ini
[program:crm-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/backend/storage/logs/queue.log
stopwaitsecs=3600
```

---

## 数据库迁移

### 迁移文件清单

按执行顺序排列：

| 序号 | 迁移文件 | 说明 |
|------|----------|------|
| 1 | `0001_01_01_000000_create_users_table` | 用户表 |
| 2 | `0001_01_01_000001_create_cache_table` | 缓存表 |
| 3 | `0001_01_01_000002_create_jobs_table` | 队列表 |
| 4 | `2026_06_20_163951_create_personal_access_tokens_table` | API Token 表 |
| 5 | `2026_06_20_163952_create_permission_tables` | 权限角色表（Spatie Permission） |
| 6 | `2026_06_20_164007_create_suppliers_table` | 供应商表 |
| 7 | `2026_06_20_164007_create_distributors_table` | 分销商表 |
| 8 | `2026_06_20_164008_create_categories_table` | 商品分类表 |
| 9 | `2026_06_20_164008_create_products_table` | 商品表 |
| 10 | `2026_06_20_164008_create_inventory_table` | 库存表 |
| 11 | `2026_06_20_164009_create_orders_table` | 订单表 |
| 12 | `2026_06_20_164009_create_order_items_table` | 订单明细表 |
| 13 | `2026_06_20_164009_create_payments_table` | 支付记录表 |
| 14 | `2026_06_20_164010_add_user_type_to_users_table` | 用户类型扩展 |
| 15 | `2026_06_21_000001_create_markets_table` | 市场/区域表 |
| 16 | `2026_06_21_000002_create_warehouses_table` | 仓库表 |
| 17 | `2026_06_21_000003_create_shipping_methods_table` | 配送方式表 |
| 18 | `2026_06_21_000004_create_currency_rates_table` | 汇率表 |
| 19 | `2026_06_21_000005_create_tax_rules_table` | 税务规则表 |
| 20 | `2026_06_21_000006_create_product_market_prices_table` | 商品市场定价表 |
| 21 | `2026_06_21_000007_create_shipments_table` | 发货单表 |
| 22 | `2026_06_21_000008_create_customs_declarations_table` | 报关单表 |
| 23 | `2026_06_21_000009_add_cross_border_fields_to_existing_tables` | 跨境字段扩展 |
| 24 | `2026_06_21_000010_create_customer_groups_tables` | 客户分组表 |
| 25 | `2026_06_21_000011_add_cross_border_fields_to_distributors_table` | 分销商跨境字段 |
| 26 | `2026_06_21_100000_refactor_for_non_self_operation` | 非自营模式重构 |

### 迁移命令

```bash
# 执行所有未执行的迁移
php artisan migrate

# 强制执行（生产环境）
php artisan migrate --force

# 回滚最后一次迁移
php artisan migrate:rollback

# 回滚所有迁移
php artisan migrate:reset

# 回滚并重新执行所有迁移
php artisan migrate:refresh

# 回滚并重跑 + 填充种子数据
php artisan migrate:refresh --seed

# 查看迁移状态
php artisan migrate:status
```

---

## 种子数据

### 数据填充器

| 填充器 | 说明 |
|--------|------|
| `DatabaseSeeder` | 总入口，调用其他所有填充器 |
| `PermissionSeeder` | 权限、角色、示例用户和基础数据 |

### PermissionSeeder 内容

#### 权限列表

| 权限组 | 权限项 |
|--------|--------|
| 供应商管理 | `supplier.view`, `supplier.create`, `supplier.edit`, `supplier.delete`, `supplier.approve` |
| 分销商管理 | `distributor.view`, `distributor.create`, `distributor.edit`, `distributor.delete`, `distributor.approve`, `distributor.view.subordinate` |
| 商品管理 | `product.view`, `product.create`, `product.edit`, `product.delete`, `product.approve` |
| 订单管理 | `order.view`, `order.create`, `order.edit`, `order.delete`, `order.approve`, `order.ship`, `order.view.subordinate` |
| 支付管理 | `payment.view`, `payment.create`, `payment.edit`, `payment.delete`, `payment.settle`, `payment.refund` |
| 库存管理 | `inventory.view`, `inventory.edit` |
| 仓库管理 | `warehouse.view`, `warehouse.create`, `warehouse.edit`, `warehouse.delete` |
| 报表管理 | `report.view` |
| 规则管理 | `rule.manage` |
| 用户管理 | `user.manage` |

#### 角色列表

| 角色 | 代码 | 说明 |
|------|------|------|
| 平台管理员 | `platform` | 系统最高权限，管理所有资源 |
| 供应商 | `supplier` | 管理商品、库存、发货 |
| 分销商 | `distributor` | 采购商品、创建订单 |
| 区域代理 | `regional_agent` | 管理下级分销商，查看下级数据 |

#### 默认账号

| 角色 | 邮箱 | 密码 | 说明 |
|------|------|------|------|
| 平台管理员 | `admin@shearerline.com` | `password123` | 系统管理员 |
| 供应商 | `supplier@shearerline.com` | `password123` | 示例供应商账号 |
| 区域代理 | `agent@shearerline.com` | `password123` | 华北区域代理 |
| 分销商 | `distributor@shearerline.com` | `password123` | 北京批发商 |

### 种子数据命令

```bash
# 执行所有填充器
php artisan db:seed

# 执行指定填充器
php artisan db:seed --class=PermissionSeeder

# 刷新迁移并填充数据
php artisan migrate:fresh --seed

# 强制填充（生产环境）
php artisan db:seed --force
```

---

## 验收命令

### 部署验证脚本

系统提供一键验收命令，用于验证部署是否成功：

```bash
php artisan crm:verify
```

验证项包括：
- ✅ 环境配置检查
- ✅ 数据库连接测试
- ✅ 迁移完整性检查
- ✅ 权限角色数据
- ✅ 默认账号可用性
- ✅ 队列连接状态
- ✅ 缓存驱动状态
- ✅ 目录权限检查

### 快速验收清单

```bash
# 1. 环境检查
php artisan about

# 2. 数据库连接测试
php artisan db:show

# 3. 迁移状态检查
php artisan migrate:status

# 4. 路由列表
php artisan route:list --path=api

# 5. 权限数据检查
php artisan tinker --execute="echo 'Roles: ' . Spatie\Permission\Models\Role::count() . PHP_EOL; echo 'Permissions: ' . Spatie\Permission\Models\Permission::count() . PHP_EOL;"

# 6. 用户数据检查
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count() . PHP_EOL; echo 'Suppliers: ' . App\Models\Supplier::count() . PHP_EOL; echo 'Distributors: ' . App\Models\Distributor::count() . PHP_EOL;"

# 7. 队列状态
php artisan queue:failed

# 8. 缓存测试
php artisan cache:clear

# 9. 配置缓存
php artisan config:cache

# 10. 路由缓存
php artisan route:cache
```

### API 验收测试

```bash
# 测试登录接口
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@shearerline.com","password":"password123"}'

# 测试获取用户信息（需替换 TOKEN）
curl http://localhost:8000/api/me \
  -H "Authorization: Bearer {TOKEN}"

# 测试仪表盘
curl http://localhost:8000/api/dashboard \
  -H "Authorization: Bearer {TOKEN}"
```

---

## 生产环境部署

### 优化配置

```bash
# 配置缓存
php artisan config:cache

# 路由缓存
php artisan route:cache

# 视图缓存
php artisan view:cache

# 事件缓存
php artisan event:cache
```

### 目录权限

```bash
# 设置存储目录权限
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 设置所有者（根据实际 web 服务器用户调整）
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### 部署脚本示例

```bash
#!/bin/bash
set -e

cd /path/to/backend

# 1. 拉取代码
git pull origin main

# 2. 安装依赖
composer install --no-dev --optimize-autoloader

# 3. 数据库迁移
php artisan migrate --force

# 4. 缓存优化
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. 重启队列
php artisan queue:restart

# 6. 部署验证
php artisan crm:verify

echo "部署完成！"
```

### Nginx 配置示例

```nginx
server {
    listen 80;
    server_name crm.example.com;
    root /path/to/backend/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 开发模式

### 一键启动开发环境

```bash
composer dev
```

该命令会同时启动：
- PHP 开发服务器（`php artisan serve`）
- 队列监听（`php artisan queue:listen`）
- 日志查看（`php artisan pail`）
- Vite 热更新（`npm run dev`）

### 运行测试

```bash
# 运行所有测试
composer test

# 或使用 artisan
php artisan test

# 运行指定测试类
php artisan test --filter=ExampleTest

# 生成覆盖率报告
php artisan test --coverage
```

### 代码风格检查

```bash
# 检查代码风格
./vendor/bin/pint --test

# 自动修复
./vendor/bin/pint
```

---

## 故障排查

### 常见问题

**Q: 迁移时报外键约束错误？**
A: 确保迁移执行顺序正确，或使用 `php artisan migrate:fresh` 重建数据库。

**Q: 权限不生效？**
A: 执行 `php artisan permission:cache-reset` 清除权限缓存。

**Q: 队列任务不执行？**
A: 检查 `QUEUE_CONNECTION` 配置，确保队列 worker 正在运行。

**Q: API 认证失败？**
A: 检查 `SANCTUM_STATEFUL_DOMAINS` 配置，确保包含前端域名。

### 日志查看

```bash
# 实时查看日志
php artisan pail

# 查看 Laravel 日志
tail -f storage/logs/laravel.log

# 查看队列日志
tail -f storage/logs/queue.log
```

---

## 技术栈

- **框架**: Laravel 13.x
- **认证**: Laravel Sanctum
- **权限**: Spatie Permission
- **数据库**: SQLite / MySQL / PostgreSQL
- **队列**: Database / Redis
- **缓存**: Database / Redis / File
- **前端**: Vue 3 + Vite
- **测试**: PHPUnit

---

## License

MIT License
