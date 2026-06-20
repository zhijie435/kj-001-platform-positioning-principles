<?php

namespace App\Http\Controllers;

use App\Models\Distributor;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return match (true) {
            $user->isPlatform() => $this->platformStats(),
            $user->isSupplier() => $this->supplierStats($user),
            default => $this->distributorStats($user),
        };
    }

    private function platformStats(): array
    {
        $escrowDeposited = Payment::where('type', 'escrow_deposit')
            ->where('status', '!=', 'failed')
            ->sum('amount');
        $escrowReleased = Payment::where('type', 'escrow_release')
            ->where('status', '!=', 'failed')
            ->sum('amount');
        $platformFees = Payment::where('type', 'platform_fee')
            ->where('status', '!=', 'failed')
            ->sum('amount');
        $refunded = Payment::where('type', 'refund')
            ->where('status', '!=', 'failed')
            ->sum('amount');
        $escrowBalance = $escrowDeposited - $escrowReleased - $refunded;

        return [
            'role' => 'platform',
            'counts' => [
                'suppliers' => Supplier::count(),
                'distributors' => Distributor::count(),
                'products' => Product::count(),
                'orders' => Order::count(),
            ],
            'order_stats' => $this->orderStatusStats(Order::query()),
            'escrow' => [
                'total_deposited' => $escrowDeposited,
                'total_released' => $escrowReleased,
                'total_refunded' => $refunded,
                'current_balance' => max(0, $escrowBalance),
                'platform_fees' => $platformFees,
            ],
            'gmv' => [
                'total' => Order::sum('total'),
                'paid' => Order::sum('paid_amount'),
            ],
            'pending_approvals' => [
                'suppliers' => Supplier::where('status', 'pending')->count(),
                'distributors' => Distributor::where('status', 'pending')->count(),
                'orders' => Order::where('status', 'pending')->count(),
            ],
            'recent_orders' => Order::with(['supplier:id,name', 'distributor:id,name'])
                ->latest()->limit(5)->get(),
        ];
    }

    private function supplierStats($user): array
    {
        $supplierId = $user->supplier_id;

        return [
            'role' => 'supplier',
            'counts' => [
                'products' => Product::where('supplier_id', $supplierId)->count(),
                'orders' => Order::where('supplier_id', $supplierId)->count(),
                'inventory' => \App\Models\Inventory::where('supplier_id', $supplierId)->count(),
            ],
            'order_stats' => $this->orderStatusStats(Order::where('supplier_id', $supplierId)),
            'revenue' => [
                'total' => Order::where('supplier_id', $supplierId)->sum('total'),
                'paid' => Order::where('supplier_id', $supplierId)->sum('paid_amount'),
            ],
            'recent_orders' => Order::with(['distributor:id,name'])
                ->where('supplier_id', $supplierId)->latest()->limit(5)->get(),
            'low_stock_products' => Product::where('supplier_id', $supplierId)
                ->whereColumn('stock_quantity', '<=', 'safety_stock')
                ->limit(5)->get(['id', 'name', 'sku', 'stock_quantity', 'safety_stock']),
        ];
    }

    private function distributorStats($user): array
    {
        $query = Order::visibleTo($user);

        return [
            'role' => $user->isRegionalAgent() ? 'regional_agent' : 'wholesaler',
            'counts' => [
                'orders' => (clone $query)->count(),
                'sub_distributors' => $user->isRegionalAgent() && $user->distributor
                    ? count($user->distributor->descendantIds())
                    : 0,
            ],
            'order_stats' => $this->orderStatusStats(clone $query),
            'revenue' => [
                'total' => (clone $query)->sum('total'),
                'paid' => (clone $query)->sum('paid_amount'),
            ],
            'recent_orders' => (clone $query)->with(['supplier:id,name'])
                ->latest()->limit(5)->get(),
        ];
    }

    private function orderStatusStats($query): array
    {
        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];

        $result = [];
        foreach ($statuses as $status) {
            $result[$status] = (clone $query)->where('status', $status)->count();
        }

        return $result;
    }
}
