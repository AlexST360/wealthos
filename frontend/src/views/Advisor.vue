<template>
  <div class="flex gap-4 max-w-6xl" style="height: calc(100vh - 8rem)">
    <!-- Sidebar sesiones -->
    <aside class="w-56 flex flex-col gap-3 flex-shrink-0">
      <button @click="startNewSession" class="btn-primary w-full text-sm py-2.5">
        ✨ Nueva conversación
      </button>

      <!-- Info de capacidades -->
      <div class="card-sm text-xs space-y-2">
        <p class="font-semibold text-gray-300 flex items-center gap-1.5">🤖 ¿Qué puede hacer?</p>
        <ul class="space-y-1 text-gray-500">
          <li class="flex items-start gap-1.5"><span class="text-emerald-500 mt-0.5">✓</span>Analizar tu portafolio</li>
          <li class="flex items-start gap-1.5"><span class="text-emerald-500 mt-0.5">✓</span>Revisar tus gastos y metas</li>
          <li class="flex items-start gap-1.5"><span class="text-emerald-500 mt-0.5">✓</span>Dar consejos personalizados</li>
          <li class="flex items-start gap-1.5"><span class="text-emerald-500 mt-0.5">✓</span>Calcular proyecciones</li>
        </ul>
        <div class="border-t border-white/5 pt-2 space-y-1">
          <p class="font-semibold text-gray-400">⚠ Limitaciones</p>
          <li class="flex items-start gap-1.5 text-gray-600 list-none"><span class="text-yellow-600 mt-0.5">✗</span>No reemplaza a un asesor certificado</li>
          <li class="flex items-start gap-1.5 text-gray-600 list-none"><span class="text-yellow-600 mt-0.5">✗</span>No predice precios futuros</li>
          <li class="flex items-start gap-1.5 text-gray-600 list-none"><span class="text-yellow-600 mt-0.5">✗</span>No ejecuta operaciones</li>
        </div>
      </div>

      <!-- Lista de sesiones -->
      <div class="card p-0 flex-1 overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
          <p class="text-[10px] font-semibold text-gray-600 uppercase tracking-wider">Historial</p>
          <span class="text-[10px] text-gray-700">{{ sessions.length }} chats</span>
        </div>
        <div class="flex-1 overflow-y-auto divide-y divide-white/5">
          <div v-for="s in sessions" :key="s.id"
            class="flex items-center group hover:bg-white/[0.03] transition-colors"
            :class="currentSession?.id === s.id ? 'bg-blue-600/10 border-l-2 border-blue-500' : ''">
            <button @click="loadSession(s.id)" class="flex-1 text-left px-4 py-3 min-w-0">
              <p class="text-xs text-gray-300 truncate font-medium">{{ s.title || 'Nueva conversación' }}</p>
              <p class="text-[10px] text-gray-600 mt-0.5">{{ fmtDate(s.created_at) }}</p>
            </button>
            <!-- Botón eliminar -->
            <button @click="deleteSession(s)"
              class="opacity-0 group-hover:opacity-100 mr-2 w-6 h-6 flex items-center justify-center rounded-lg text-gray-700 hover:text-red-400 hover:bg-red-500/10 transition-all flex-shrink-0">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
          <div v-if="!sessions.length" class="px-4 py-8 text-center text-xs text-gray-600">
            Sin conversaciones anteriores
          </div>
        </div>
      </div>
    </aside>

    <!-- Chat -->
    <div class="flex-1 flex flex-col card p-0 overflow-hidden min-w-0">
      <!-- Header -->
      <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600/40 to-purple-600/40 border border-white/10 flex items-center justify-center text-lg">🤖</div>
          <div>
            <h2 class="text-sm font-bold text-white">WealthOS Advisor</h2>
            <p class="text-[10px] text-emerald-400">● Groq · llama-3.3-70b · Contexto financiero activo</p>
          </div>
        </div>
        <!-- Eliminar sesión actual -->
        <button v-if="currentSession" @click="deleteSession(currentSession)"
          class="btn-ghost text-xs text-gray-600 hover:text-red-400 gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
          </svg>
          Eliminar chat
        </button>
      </div>

      <!-- Mensajes -->
      <div ref="messagesEl" class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
        <div v-if="!currentSession" class="h-full flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 rounded-3xl bg-gradient-to-br from-blue-600/30 to-purple-600/30 border border-white/10 flex items-center justify-center text-3xl mb-4">🤖</div>
          <h3 class="text-lg font-bold text-white mb-1">Asesor Financiero IA</h3>
          <p class="text-gray-500 text-sm max-w-xs mb-2">Analiza tu portafolio y finanzas personales con tus datos reales.</p>
          <p class="text-yellow-600/80 text-xs max-w-xs mb-6">No reemplaza a un asesor financiero certificado. Para estrategias de inversión complejas, consulta un profesional.</p>
          <div class="grid grid-cols-2 gap-2 mb-6 w-full max-w-sm">
            <button v-for="q in quickQuestions" :key="q" @click="startAndSend(q)"
              class="text-xs bg-white/[0.04] hover:bg-white/[0.07] border border-white/5 hover:border-white/10 text-gray-400 hover:text-gray-200 px-3 py-2 rounded-xl transition-all text-left">
              {{ q }}
            </button>
          </div>
          <button @click="startNewSession" class="btn-primary">💬 Comenzar conversación</button>
        </div>

        <template v-else>
          <div v-for="(msg, i) in messages" :key="i"
            class="flex gap-3" :class="msg.role === 'user' ? 'flex-row-reverse' : 'flex-row'">
            <div class="w-7 h-7 rounded-xl flex items-center justify-center text-sm flex-shrink-0 mt-0.5"
              :class="msg.role === 'user'
                ? 'bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-bold'
                : 'bg-white/[0.05] border border-white/10'">
              {{ msg.role === 'user' ? auth.user?.name?.[0]?.toUpperCase() : '🤖' }}
            </div>
            <div class="max-w-[75%] px-4 py-3 rounded-2xl text-sm leading-relaxed whitespace-pre-wrap"
              :class="msg.role === 'user'
                ? 'bg-blue-600 text-white rounded-tr-sm'
                : 'bg-white/[0.05] border border-white/5 text-gray-200 rounded-tl-sm'">
              {{ msg.content }}
            </div>
          </div>

          <div v-if="responding" class="flex gap-3">
            <div class="w-7 h-7 rounded-xl bg-white/[0.05] border border-white/10 flex items-center justify-center text-sm">🤖</div>
            <div class="bg-white/[0.05] border border-white/5 px-4 py-3 rounded-2xl rounded-tl-sm">
              <div class="flex gap-1.5 items-center h-4">
                <div v-for="i in 3" :key="i" class="w-1.5 h-1.5 bg-gray-500 rounded-full animate-bounce"
                  :style="{ animationDelay: (i-1)*150+'ms' }"></div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Sugerencias rápidas -->
      <div v-if="currentSession && messages.length <= 1" class="px-5 py-2 border-t border-white/5 flex gap-2 flex-wrap">
        <button v-for="q in quickQuestions" :key="q" @click="sendMessage(q)"
          class="text-[11px] bg-white/[0.04] hover:bg-white/[0.07] border border-white/5 text-gray-500 hover:text-gray-200 px-2.5 py-1.5 rounded-lg transition-all">
          {{ q }}
        </button>
      </div>

      <!-- Input -->
      <div v-if="currentSession" class="px-5 py-4 border-t border-white/5 flex-shrink-0">
        <div class="flex gap-3 items-end">
          <textarea v-model="inputMessage"
            @keydown.enter.exact.prevent="handleSend"
            class="input flex-1 resize-none"
            :style="{ minHeight: '42px', maxHeight: '120px' }"
            rows="1"
            placeholder="Escribe tu pregunta... (Enter para enviar)"
            :disabled="responding"
            @input="autoResize"></textarea>
          <button @click="handleSend" class="btn-primary px-4 py-2.5 flex-shrink-0"
            :disabled="!inputMessage.trim() || responding">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
          </button>
        </div>
        <p class="text-[10px] text-gray-700 mt-2">↵ Enter para enviar · Shift+Enter para nueva línea</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import { advisor as advisorApi } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth           = useAuthStore()
const sessions       = ref([])
const currentSession = ref(null)
const inputMessage   = ref('')
const responding     = ref(false)
const messagesEl     = ref(null)

const messages = computed(() => currentSession.value?.messages ?? [])

const quickQuestions = [
  '¿Estoy bien diversificado?',
  '¿Cómo va mi ahorro este mes?',
  '¿Qué puedo mejorar en mis finanzas?',
  '¿Cuándo podré jubilarme?',
  '¿Qué hacer con $500.000?',
  '¿Estoy gastando demasiado?',
]

const fmtDate = (d) => new Date(d).toLocaleDateString('es-CL', { day: 'numeric', month: 'short' })

async function loadSessions() {
  const { data } = await advisorApi.sessions()
  sessions.value = data.sessions
}

async function startNewSession() {
  const { data } = await advisorApi.quickStart()
  currentSession.value = data.session
  sessions.value.unshift({ id: data.session.id, title: data.session.title, created_at: new Date() })
  await scrollBottom()
}

async function startAndSend(msg) {
  await startNewSession()
  await sendMessage(msg)
}

async function loadSession(id) {
  const { data } = await advisorApi.getSession(id)
  currentSession.value = data.session
  await scrollBottom()
}

async function deleteSession(session) {
  if (!confirm('¿Eliminar esta conversación?')) return
  await advisorApi.deleteSession(session.id)
  sessions.value = sessions.value.filter(s => s.id !== session.id)
  if (currentSession.value?.id === session.id) currentSession.value = null
}

async function handleSend() {
  const msg = inputMessage.value.trim()
  if (!msg || responding.value) return
  await sendMessage(msg)
}

async function sendMessage(msg) {
  inputMessage.value = ''
  responding.value   = true
  currentSession.value.messages = [...(currentSession.value.messages ?? []),
    { role: 'user', content: msg, timestamp: new Date().toISOString() }
  ]
  await scrollBottom()
  try {
    const { data } = await advisorApi.sendMessage(currentSession.value.id, msg)
    currentSession.value.messages = data.session.messages
    const idx = sessions.value.findIndex(s => s.id === currentSession.value.id)
    if (idx !== -1) sessions.value[idx].title = data.session.title
  } catch {
    currentSession.value.messages.push({
      role: 'assistant',
      content: '⚠️ El asesor no está disponible en este momento. Intenta de nuevo en unos segundos.',
      timestamp: new Date().toISOString(),
    })
  } finally {
    responding.value = false
    await scrollBottom()
  }
}

async function scrollBottom() {
  await nextTick()
  if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
}

function autoResize(e) {
  e.target.style.height = 'auto'
  e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px'
}

onMounted(loadSessions)
</script>

<style scoped>
@keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
.animate-fadeIn { animation: fadeIn 0.2s ease; }
</style>
