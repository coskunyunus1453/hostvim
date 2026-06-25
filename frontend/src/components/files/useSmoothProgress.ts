import { useEffect, useRef, useState } from 'react'

/** Hedef yüzdeye yumuşak geçiş — axios progress olayları arasında akıcı çubuk */
export function useSmoothProgress(target: number, blend = 0.22): number {
  const [display, setDisplay] = useState(target)
  const displayRef = useRef(target)
  const targetRef = useRef(target)
  targetRef.current = Math.min(100, Math.max(0, target))

  useEffect(() => {
    let raf = 0
    const tick = () => {
      const goal = targetRef.current
      const current = displayRef.current
      const diff = goal - current

      if (Math.abs(diff) < 0.08) {
        if (current !== goal) {
          displayRef.current = goal
          setDisplay(goal)
        }
      } else {
        const next = current + diff * blend
        displayRef.current = next
        setDisplay(next)
      }

      raf = requestAnimationFrame(tick)
    }

    raf = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(raf)
  }, [blend])

  return display
}
