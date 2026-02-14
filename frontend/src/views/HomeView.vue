<script setup lang="ts">
import Button from 'primevue/button'
import Card from 'primevue/card'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div class="min-h-screen">
    <!-- Hero -->
    <section class="flex flex-col items-center justify-center text-center px-6 py-24 bg-surface-0">
      <h1 class="text-5xl font-bold mb-4">Helpdesk</h1>
      <p class="text-xl text-muted-color max-w-2xl mb-8">
        System zgłoszeń, który pozwala sprawnie zarządzać ticketami, organizować pracę zespołu i śledzić postępy w czasie rzeczywistym.
      </p>
      <div class="flex gap-4">
        <template v-if="auth.isAuthenticated">
          <router-link to="/dashboard">
            <Button label="Przejdź do panelu" icon="pi pi-arrow-right" />
          </router-link>
        </template>
        <template v-else>
          <router-link to="/login">
            <Button label="Zaloguj się" icon="pi pi-sign-in" />
          </router-link>
          <router-link to="/register">
            <Button label="Zarejestruj się" icon="pi pi-user-plus" severity="secondary" outlined />
          </router-link>
        </template>
      </div>
    </section>

    <!-- Features -->
    <section class="px-6 py-16 bg-surface-50">
      <h2 class="text-3xl font-bold text-center mb-10">Funkcjonalności</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-ticket text-xl text-primary" />
              <span>Tickety</span>
            </div>
          </template>
          <template #content>
            Twórz, przypisuj i śledź zgłoszenia. Ustalaj priorytety i statusy, aby utrzymać porządek w pracy zespołu.
          </template>
        </Card>

        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-users text-xl text-primary" />
              <span>Organizacja i role</span>
            </div>
          </template>
          <template #content>
            Zarządzaj użytkownikami i rolami. Administratorzy kontrolują dostęp, a agenci obsługują zgłoszenia.
          </template>
        </Card>

        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-chart-bar text-xl text-primary" />
              <span>Dashboard i analityka</span>
            </div>
          </template>
          <template #content>
            Podgląd statystyk w czasie rzeczywistym. Monitoruj liczbę otwartych zgłoszeń, czasy odpowiedzi i wydajność zespołu.
          </template>
        </Card>
      </div>
    </section>
  </div>
</template>
