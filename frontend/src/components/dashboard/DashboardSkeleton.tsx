import clsx from 'clsx'

function Shimmer({ className }: { className?: string }) {
  return (
    <div
      className={clsx(
        'rounded-lg bg-gradient-to-r from-gray-200 via-gray-100 to-gray-200 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800',
        'animate-[shimmer_1.4s_ease-in-out_infinite] bg-[length:200%_100%]',
        className,
      )}
    />
  )
}

function StatCardSkeleton() {
  return (
    <div className="card p-4">
      <div className="flex items-center justify-between gap-3">
        <Shimmer className="h-10 w-10 rounded-xl" />
        <Shimmer className="h-8 w-12" />
      </div>
      <Shimmer className="mt-3 h-4 w-28" />
      <Shimmer className="mt-2 h-3 w-20" />
    </div>
  )
}

function DiskCardSkeleton() {
  return (
    <div className="card p-5 sm:col-span-2 lg:col-span-2">
      <div className="flex items-start justify-between gap-4">
        <div className="flex-1 space-y-2">
          <Shimmer className="h-3 w-24" />
          <Shimmer className="h-8 w-40" />
          <Shimmer className="h-4 w-20" />
        </div>
        <Shimmer className="h-10 w-10 rounded-xl" />
      </div>
      <Shimmer className="mt-4 h-2 w-full rounded-full" />
    </div>
  )
}

function ChartSkeleton() {
  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
      {[0, 1, 2].map((i) => (
        <div key={i} className="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
          <Shimmer className="mx-auto h-28 w-28 rounded-full" />
          <Shimmer className="mx-auto mt-3 h-4 w-24" />
        </div>
      ))}
    </div>
  )
}

export default function DashboardSkeleton({ showCharts = false }: { showCharts?: boolean }) {
  return (
    <div className="space-y-6" aria-busy="true" aria-live="polite">
      <div className="space-y-2">
        <Shimmer className="h-8 w-48" />
        <Shimmer className="h-4 w-56" />
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <DiskCardSkeleton />
        <StatCardSkeleton />
        <StatCardSkeleton />
        <StatCardSkeleton />
      </div>

      {showCharts && (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div className="card p-6 lg:col-span-2">
            <Shimmer className="mb-4 h-5 w-36" />
            <ChartSkeleton />
          </div>
          <div className="card p-6 space-y-4">
            <Shimmer className="h-5 w-32" />
            <Shimmer className="h-20 w-full rounded-xl" />
            <Shimmer className="h-20 w-full rounded-xl" />
            <Shimmer className="h-20 w-full rounded-xl" />
          </div>
        </div>
      )}
    </div>
  )
}
