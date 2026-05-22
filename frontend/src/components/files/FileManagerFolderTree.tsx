import { useState } from 'react'
import clsx from 'clsx'
import { ChevronDown, ChevronRight, Folder, FolderOpen, Globe } from 'lucide-react'

export type FolderTreeNode = {
  name: string
  path: string
  children?: FolderTreeNode[]
}

type Props = {
  tree: FolderTreeNode[]
  currentPath: string
  documentRootRel?: string
  rootLabel: string
  docrootLabel: string
  onNavigate: (path: string) => void
  loading?: boolean
}

function TreeNodeRow({
  node,
  depth,
  currentPath,
  onNavigate,
}: {
  node: FolderTreeNode
  depth: number
  currentPath: string
  onNavigate: (path: string) => void
}) {
  const hasKids = (node.children?.length ?? 0) > 0
  const [open, setOpen] = useState(depth < 1)
  const active = currentPath === node.path || (node.path !== '' && currentPath.startsWith(`${node.path}/`))

  return (
    <li>
      <div
        className={clsx(
          'flex min-w-0 items-center gap-0.5 rounded-md pr-1 text-left text-xs',
          active && 'bg-primary-100/90 text-primary-900 dark:bg-primary-950/50 dark:text-primary-100',
        )}
        style={{ paddingLeft: `${depth * 12 + 4}px` }}
      >
        {hasKids ? (
          <button
            type="button"
            className="shrink-0 rounded p-0.5 text-gray-500 hover:bg-gray-200/80 dark:hover:bg-gray-700"
            aria-expanded={open}
            onClick={() => setOpen((o) => !o)}
          >
            {open ? <ChevronDown className="h-3.5 w-3.5" /> : <ChevronRight className="h-3.5 w-3.5" />}
          </button>
        ) : (
          <span className="inline-block w-4 shrink-0" />
        )}
        <button
          type="button"
          className="flex min-w-0 flex-1 items-center gap-1.5 py-1.5 text-left font-medium text-gray-800 hover:text-primary-700 dark:text-gray-200 dark:hover:text-primary-300"
          onClick={() => onNavigate(node.path)}
          title={node.path}
        >
          {open && active ? (
            <FolderOpen className="h-3.5 w-3.5 shrink-0 text-amber-500" />
          ) : (
            <Folder className="h-3.5 w-3.5 shrink-0 text-amber-500/90" />
          )}
          <span className="truncate">{node.name}</span>
        </button>
      </div>
      {hasKids && open && (
        <ul className="mt-0.5 space-y-0.5">
          {node.children!.map((child) => (
            <TreeNodeRow
              key={child.path}
              node={child}
              depth={depth + 1}
              currentPath={currentPath}
              onNavigate={onNavigate}
            />
          ))}
        </ul>
      )}
    </li>
  )
}

export default function FileManagerFolderTree({
  tree,
  currentPath,
  documentRootRel,
  rootLabel,
  docrootLabel,
  onNavigate,
  loading,
}: Props) {
  const rootActive = currentPath === ''

  return (
    <nav className="flex min-h-0 flex-col" aria-label={rootLabel}>
      <ul className="space-y-0.5 overflow-y-auto overscroll-contain pr-0.5 max-h-[min(42vh,20rem)] lg:max-h-[min(58vh,28rem)]">
        <li>
          <button
            type="button"
            className={clsx(
              'flex w-full min-w-0 items-center gap-2 rounded-md px-2 py-2 text-left text-xs font-semibold',
              rootActive
                ? 'bg-primary-100/90 text-primary-900 dark:bg-primary-950/50 dark:text-primary-100'
                : 'text-gray-800 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800',
            )}
            onClick={() => onNavigate('')}
          >
            <Folder className="h-4 w-4 shrink-0 text-amber-500" />
            <span className="truncate">{rootLabel}</span>
          </button>
        </li>
        {documentRootRel ? (
          <li>
            <button
              type="button"
              className={clsx(
                'flex w-full min-w-0 items-center gap-2 rounded-md px-2 py-1.5 pl-3 text-left text-xs font-medium',
                currentPath === documentRootRel
                  ? 'bg-emerald-100/90 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100'
                  : 'text-emerald-800 hover:bg-gray-100 dark:text-emerald-300 dark:hover:bg-gray-800',
              )}
              onClick={() => onNavigate(documentRootRel)}
              title={documentRootRel}
            >
              <Globe className="h-3.5 w-3.5 shrink-0" />
              <span className="truncate">{docrootLabel}</span>
            </button>
          </li>
        ) : null}
        {loading ? (
          <li className="px-2 py-2 text-xs text-gray-500">{rootLabel}…</li>
        ) : (
          tree.map((node) => (
            <TreeNodeRow key={node.path} node={node} depth={0} currentPath={currentPath} onNavigate={onNavigate} />
          ))
        )}
      </ul>
    </nav>
  )
}
