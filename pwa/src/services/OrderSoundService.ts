type AudioContextConstructor = new () => AudioContext

/** Одна нота сигнала: частота в герцах, длительность и пауза от начала в секундах. */
type SignalNote = {
  frequency: number
  duration: number
  delay: number
}

/** Сколько ждём разблокировку звука, прежде чем считать сигнал неактуальным. */
const RESUME_TIMEOUT_MS = 800

/** Сигнал исполнителю «новый заказ»: восходящая пара G#5 → C#6. */
const NEW_ORDER_OFFER_SIGNAL: SignalNote[] = [
  { frequency: 830.61, duration: 0.18, delay: 0 },
  { frequency: 1108.73, duration: 0.34, delay: 0.18 },
]

/** Сигнал заказчику «исполнитель откликнулся»: восходящее трезвучие C6 → E6 → G6. */
const ORDER_TAKEN_SIGNAL: SignalNote[] = [
  { frequency: 1046.5, duration: 0.14, delay: 0 },
  { frequency: 1318.51, duration: 0.14, delay: 0.13 },
  { frequency: 1567.98, duration: 0.3, delay: 0.26 },
]

/**
 * Возвращает доступный конструктор AudioContext (Safari отдаёт только префиксный).
 */
function audioContextConstructor(): AudioContextConstructor | null {
  if (typeof window === 'undefined') {
    return null
  }

  const global = window as unknown as {
    AudioContext?: AudioContextConstructor
    webkitAudioContext?: AudioContextConstructor
  }

  return global.AudioContext ?? global.webkitAudioContext ?? null
}

/**
 * Звуковое сопровождение событий заказов.
 *
 * Сигнал синтезируется через Web Audio API: не нужны бинарные ассеты,
 * звук работает офлайн и не зависит от кэша service worker.
 */
export class OrderSoundService {
  private context: AudioContext | null = null
  private unlockBound = false

  /**
   * Разблокирует вывод звука после первого жеста пользователя.
   *
   * Браузеры не разрешают запускать AudioContext без взаимодействия со страницей,
   * поэтому подписку на жест нужно повесить заранее — до прихода realtime-события.
   */
  unlock(): void {
    if (this.unlockBound || typeof window === 'undefined') {
      return
    }
    this.unlockBound = true

    const resume = (): void => {
      const context = this.audioContext()
      if (context && context.state === 'suspended') {
        void context.resume()
      }
    }

    window.addEventListener('pointerdown', resume, { passive: true })
    window.addEventListener('keydown', resume)
    window.addEventListener('touchstart', resume, { passive: true })
  }

  /**
   * Короткий двойной сигнал «новый заказ» для предложения исполнителю.
   */
  async playNewOrderOffer(): Promise<void> {
    await this.play(NEW_ORDER_OFFER_SIGNAL)
  }

  /**
   * Восходящий сигнал «исполнитель откликнулся и взял заказ в работу» для заказчика.
   */
  async playOrderTaken(): Promise<void> {
    await this.play(ORDER_TAKEN_SIGNAL)
  }

  /**
   * Проигрывает последовательность нот.
   *
   * Молча выходит, если браузер ещё не разрешил воспроизведение.
   */
  private async play(signal: SignalNote[]): Promise<void> {
    const context = this.audioContext()
    if (!context) {
      return
    }

    if (context.state !== 'running' && !(await this.resumeContext(context))) {
      return
    }

    const start = context.currentTime + 0.02
    signal.forEach((note) => {
      this.tone(context, start + note.delay, note.frequency, note.duration)
    })
  }

  /**
   * Пытается запустить вывод звука и дождаться разрешения браузера.
   *
   * Без жеста пользователя resume() может ждать его неопределённо долго,
   * поэтому ограничиваем ожидание — иначе сигнал прозвучит с опозданием.
   */
  private async resumeContext(context: AudioContext): Promise<boolean> {
    try {
      const resumed = await Promise.race([
        context.resume().then(() => true),
        new Promise<boolean>((resolve) => {
          window.setTimeout(() => resolve(false), RESUME_TIMEOUT_MS)
        }),
      ])
      if (!resumed) {
        return false
      }
    } catch {
      return false
    }

    return context.state === 'running'
  }

  /**
   * Играет одну ноту с плавной огибающей, чтобы не было щелчка на старте.
   */
  private tone(context: AudioContext, startAt: number, frequency: number, duration: number): void {
    const oscillator = context.createOscillator()
    const gain = context.createGain()

    oscillator.type = 'sine'
    oscillator.frequency.value = frequency

    gain.gain.setValueAtTime(0, startAt)
    gain.gain.linearRampToValueAtTime(0.22, startAt + 0.02)
    gain.gain.exponentialRampToValueAtTime(0.0001, startAt + duration)

    oscillator.connect(gain)
    gain.connect(context.destination)
    oscillator.start(startAt)
    oscillator.stop(startAt + duration + 0.02)
  }

  /**
   * Ленивая инициализация AudioContext: не создаём его до первого звука.
   */
  private audioContext(): AudioContext | null {
    if (this.context) {
      return this.context
    }

    const Constructor = audioContextConstructor()
    if (!Constructor) {
      return null
    }

    this.context = new Constructor()
    return this.context
  }
}

export const orderSoundService = new OrderSoundService()
