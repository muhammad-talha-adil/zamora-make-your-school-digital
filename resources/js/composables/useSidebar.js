import { ref, onMounted, onUnmounted } from 'vue'

const isOpen = ref(false)

export function useSidebar() {
  const open = () => {
    isOpen.value = true
  }

  const close = () => {
    isOpen.value = false
  }

  const toggle = () => {
    isOpen.value = !isOpen.value
  }

  const handleKeydown = (event) => {
    if (event.key === 'Escape') {
      close()
    }
  }

  onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
  })

  return {
    isOpen,
    open,
    close,
    toggle,
  }
}