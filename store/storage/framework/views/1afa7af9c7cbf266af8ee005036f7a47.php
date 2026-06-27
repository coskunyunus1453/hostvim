<?php
    $sceneClass = $class ?? 'max-w-xl';
?>

<div class="hv-hero-scene <?php echo e($sceneClass); ?> mx-auto" aria-hidden="true">
    <div class="hv-hero-glow hv-hero-glow-a"></div>
    <div class="hv-hero-glow hv-hero-glow-b"></div>

    <svg class="hv-hero-lines" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path class="hv-hero-data-line" d="M200 120 L80 60" stroke="currentColor" stroke-width="1.5" opacity="0.35"/>
        <path class="hv-hero-data-line hv-hero-data-line-delay" d="M200 120 L320 70" stroke="currentColor" stroke-width="1.5" opacity="0.35"/>
        <path class="hv-hero-data-line hv-hero-data-line-delay-2" d="M200 200 L60 280" stroke="currentColor" stroke-width="1.5" opacity="0.3"/>
        <path class="hv-hero-data-line hv-hero-data-line-delay-3" d="M200 200 L340 290" stroke="currentColor" stroke-width="1.5" opacity="0.3"/>
        <circle class="hv-hero-packet hv-hero-packet-1" cx="140" cy="90" r="3" fill="currentColor"/>
        <circle class="hv-hero-packet hv-hero-packet-2" cx="260" cy="95" r="3" fill="currentColor"/>
        <circle class="hv-hero-packet hv-hero-packet-3" cx="130" cy="240" r="3" fill="currentColor"/>
        <circle class="hv-hero-packet hv-hero-packet-4" cx="270" cy="245" r="3" fill="currentColor"/>
    </svg>

    <div class="hv-hero-node hv-hero-node-cloud hv-hero-float-slow">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M7 18a4 4 0 01-.88-7.9A5.5 5.5 0 0117.5 8.5 4.5 4.5 0 0119 17H7z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Cloud</span>
        <span class="hv-hero-node-ping"></span>
    </div>

    <div class="hv-hero-node hv-hero-node-vps hv-hero-float-delay">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="4" y="4" width="16" height="6" rx="1"/><rect x="4" y="14" width="16" height="6" rx="1"/>
            <circle cx="7" cy="7" r="1" fill="currentColor"/><circle cx="7" cy="17" r="1" fill="currentColor"/>
        </svg>
        <span>VPS</span>
    </div>

    <div class="hv-hero-node hv-hero-node-domain hv-hero-float-slow-2">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/>
        </svg>
        <span>Domain</span>
    </div>

    <div class="hv-hero-rack hv-hero-float-center">
        <div class="hv-hero-rack-top">
            <span class="hv-hero-led hv-hero-led-green"></span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-hv-muted">TR-DC-01</span>
            <span class="hv-hero-led hv-hero-led-amber hv-hero-led-delay"></span>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['Web Hosting', 'VPS Cluster', 'VDS Premium', 'DNS Edge']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="hv-hero-rack-unit" style="--hv-unit-i: <?php echo e($i); ?>">
                <div class="flex items-center gap-2">
                    <span class="hv-hero-led <?php echo e($i % 2 === 0 ? 'hv-hero-led-green' : 'hv-hero-led-primary'); ?>"></span>
                    <span class="text-xs font-semibold text-hv-text"><?php echo e($unit); ?></span>
                </div>
                <div class="hv-hero-rack-bar">
                    <div class="hv-hero-rack-bar-fill" style="--hv-bar-w: <?php echo e(88 - $i * 5); ?>%"></div>
                </div>
                <span class="text-[10px] font-bold text-hv-secondary"><?php echo e(88 - $i * 5); ?>%</span>
            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <div class="hv-hero-rack-footer">
            <span class="text-[10px] text-hv-muted">NVMe · LiteSpeed · DDoS</span>
            <span class="hv-hero-uptime-badge">99.9% uptime</span>
        </div>
    </div>

    <div class="hv-hero-badge hv-hero-badge-nvme hv-hero-orbit-badge">NVMe SSD</div>
    <div class="hv-hero-badge hv-hero-badge-ddos hv-hero-orbit-badge-2">DDoS Koruma</div>
    <div class="hv-hero-badge hv-hero-badge-cpanel hv-hero-orbit-badge-3">cPanel</div>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/hostvim/store/resources/views/partials/hero/illustration.blade.php ENDPATH**/ ?>