<script setup lang="ts">
import Button from 'primevue/button'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div class="min-h-screen bg-surface-0">
    <!-- Navbar -->
    <nav class="flex items-center justify-between px-8 py-4 border-b border-surface-200">
      <div class="flex items-center gap-2">
        <i class="pi pi-shield text-primary text-xl"></i>
        <span class="font-bold text-lg tracking-tight">Helpdesk</span>
      </div>
      <div class="flex items-center gap-3">
        <template v-if="auth.isAuthenticated">
          <router-link to="/dashboard">
            <Button label="Przejdź do panelu" icon="pi pi-arrow-right" size="small" />
          </router-link>
        </template>
        <template v-else>
          <router-link to="/login">
            <Button label="Zaloguj się" severity="secondary" text size="small" />
          </router-link>
          <router-link to="/register">
            <Button label="Zarejestruj się" size="small" />
          </router-link>
        </template>
      </div>
    </nav>

    <!-- Hero -->
    <section class="flex flex-col items-center justify-center text-center px-6 py-28">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-medium mb-6">
        <i class="pi pi-bolt text-xs"></i>
        System obsługi zgłoszeń
      </div>
      <h1 class="text-5xl font-extrabold tracking-tight mb-5 max-w-3xl leading-tight">
        Zarządzaj zgłoszeniami <br>
        <span class="text-primary">sprawnie i przejrzyście</span>
      </h1>
      <p class="text-lg text-muted-color max-w-2xl mb-10 leading-relaxed">
        Twórz tickety, przypisuj je do zespołu, śledź postępy — wszystko w jednym miejscu. Helpdesk, który pomaga utrzymać porządek.
      </p>
      <div class="flex gap-4">
        <template v-if="auth.isAuthenticated">
          <router-link to="/dashboard">
            <Button label="Przejdź do panelu" icon="pi pi-arrow-right" size="large" />
          </router-link>
        </template>
        <template v-else>
          <router-link to="/register">
            <Button label="Rozpocznij za darmo" icon="pi pi-arrow-right" icon-pos="right" size="large" />
          </router-link>
          <router-link to="/login">
            <Button label="Zaloguj się" severity="secondary" outlined size="large" />
          </router-link>
        </template>
      </div>
    </section>

    <!-- Features -->
    <section class="flex justify-center px-6 py-20 bg-surface-50 border-t border-surface-200">
      <div style="width: 100%; max-width: 64rem;">
        <div class="text-center mb-14">
          <h2 class="text-3xl font-bold tracking-tight mb-3">Co oferuje Helpdesk?</h2>
          <p class="text-muted-color text-lg">Wszystkie narzędzia potrzebne do sprawnej obsługi zgłoszeń</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center mb-4">
              <i class="pi pi-ticket text-blue-500 text-xl"></i>
            </div>
            <h3 class="font-semibold text-lg mb-2">Tickety</h3>
            <p class="text-muted-color text-sm leading-relaxed">
              Twórz, przypisuj i śledź zgłoszenia. Ustalaj priorytety i statusy, aby utrzymać porządek w pracy zespołu.
            </p>
          </div>

          <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 rounded-lg bg-amber-50 flex items-center justify-center mb-4">
              <i class="pi pi-users text-amber-500 text-xl"></i>
            </div>
            <h3 class="font-semibold text-lg mb-2">Organizacja i role</h3>
            <p class="text-muted-color text-sm leading-relaxed">
              Zarządzaj użytkownikami i rolami. Administratorzy kontrolują dostęp, a agenci obsługują zgłoszenia.
            </p>
          </div>

          <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 rounded-lg bg-green-50 flex items-center justify-center mb-4">
              <i class="pi pi-chart-bar text-green-500 text-xl"></i>
            </div>
            <h3 class="font-semibold text-lg mb-2">Dashboard i analityka</h3>
            <p class="text-muted-color text-sm leading-relaxed">
              Podgląd statystyk w czasie rzeczywistym. Monitoruj liczbę otwartych zgłoszeń i wydajność zespołu.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="px-6 py-8 text-center text-muted-color text-sm border-t border-surface-200">
      Helpdesk &copy; {{ new Date().getFullYear() }}
    </footer>
  </div>
</template>
