<template>
    <div class="max-w-6xl mx-auto space-y-6 overflow-x-hidden">
        <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase italic leading-none">Settings</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Configure your application</p>
        </div>

        <div class="flex gap-6">
            <!-- Sidebar Navigation -->
            <nav class="w-64 shrink-0 space-y-1">
                <button v-for="section in visibleSections" :key="section.id"
                        @click="activeSection = section.id"
                        :class="[
                            'w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-left transition-all border',
                            activeSection === section.id
                                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 shadow-sm'
                                : 'bg-white border-transparent text-slate-600 hover:bg-slate-50 hover:border-slate-200'
                        ]">
                    <component :is="section.icon" class="w-5 h-5 shrink-0" />
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-tight">{{ section.label }}</p>
                        <p class="text-[9px] font-bold text-slate-400 mt-0.5 truncate">{{ section.description }}</p>
                    </div>
                    <span v-if="section.comingSoon"
                          class="ml-auto text-[8px] font-black px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-700 uppercase shrink-0">Soon</span>
                </button>
            </nav>

            <!-- Content Area -->
            <div class="flex-1 min-w-0">
                <!-- Security -->
                <div v-if="activeSection === 'security'">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                            <div>
                                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Two-Factor Authentication</h2>
                                <p class="text-[9px] font-bold text-slate-400 mt-1">Add an extra layer of security to your account</p>
                            </div>
                            <span v-if="twoFactorEnabled"
                                  class="text-[9px] font-black px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-700 uppercase">Active</span>
                            <span v-else
                                  class="text-[9px] font-black px-2.5 py-1 rounded-md bg-slate-100 text-slate-500 uppercase">Off</span>
                        </div>

                        <div class="p-6">
                            <div v-if="!twoFactorEnabled && !showSetup" class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-slate-700">Protect your account</p>
                                    <p class="text-[10px] text-slate-400 font-medium mt-1">Use an authenticator app like Google Authenticator or Microsoft Authenticator</p>
                                </div>
                                <button @click="enable2FA"
                                        :disabled="loading"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-blue-200 active:scale-95 transition-all uppercase disabled:opacity-40">
                                    {{ loading ? 'Setting up...' : 'Enable 2FA' }}
                                </button>
                            </div>

                            <div v-if="showSetup" class="space-y-6">
                                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Step 1: Scan QR Code</p>
                                    <div class="flex items-start gap-8">
                                        <div v-if="qrCode" class="bg-white p-3 rounded-xl shadow-sm border border-slate-200" v-html="qrCode"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-slate-600 mb-2">Or enter this secret key manually:</p>
                                            <code class="block bg-slate-800 text-emerald-300 text-xs font-mono p-3 rounded-xl select-all break-all">{{ secret }}</code>
                                            <p class="text-[10px] text-amber-600 font-bold mt-3 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                                Save your recovery codes before confirming. You won't see them again.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="newRecoveryCodes.length" class="bg-amber-50 rounded-2xl p-6 border border-amber-200">
                                    <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest mb-3">Recovery Codes (save these!)</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <code v-for="(rc, i) in newRecoveryCodes" :key="i"
                                              class="bg-white text-amber-900 text-xs font-mono p-2 rounded-lg border border-amber-100 select-all text-center">
                                            {{ rc }}
                                        </code>
                                    </div>
                                </div>

                                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Step 2: Confirm Setup</p>
                                    <p class="text-xs font-bold text-slate-600 mb-3">Enter the 6-digit code from your authenticator app</p>
                                    <div class="flex gap-3 items-start">
                                        <input v-model="confirmCode"
                                               type="text"
                                               autocomplete="off"
                                               inputmode="numeric"
                                               maxlength="6"
                                               placeholder="000 000"
                                               class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-white w-48 transition-all text-center text-xl tracking-[0.3em] font-mono" />
                                        <button @click="confirmSetup"
                                                :disabled="loading || confirmCode.length < 6"
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3.5 rounded-xl text-xs font-black transition-all active:scale-95 uppercase disabled:opacity-40">
                                            {{ loading ? 'Verifying...' : 'Confirm' }}
                                        </button>
                                    </div>
                                    <p v-if="confirmError" class="text-red-500 text-[10px] font-bold mt-2">{{ confirmError }}</p>
                                </div>

                                <button @click="cancelSetup"
                                        class="text-[10px] font-black text-slate-400 hover:text-slate-600 uppercase transition-colors">
                                    &larr; Cancel
                                </button>
                            </div>

                            <div v-if="twoFactorEnabled && !showSetup" class="space-y-5">
                                <div class="flex items-center gap-2 text-emerald-700 bg-emerald-50 rounded-xl px-4 py-3 border border-emerald-200">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-xs font-bold">Two-factor authentication is active on your account.</p>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button @click="showRecoveryCodes = !showRecoveryCodes"
                                            class="px-5 py-2.5 rounded-xl text-xs font-black border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all uppercase">
                                        {{ showRecoveryCodes ? 'Hide Codes' : 'View Recovery Codes' }}
                                    </button>
                                    <button @click="regenerateCodes"
                                            :disabled="loading"
                                            class="px-5 py-2.5 rounded-xl text-xs font-black border border-amber-200 text-amber-700 hover:bg-amber-50 transition-all uppercase disabled:opacity-40">
                                        Regenerate Codes
                                    </button>
                                </div>

                                <div v-if="showRecoveryCodes" class="bg-amber-50 rounded-2xl p-6 border border-amber-200">
                                    <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest mb-3">Recovery Codes</p>
                                    <div v-if="recoveryCodes.length" class="grid grid-cols-2 gap-2">
                                        <code v-for="(rc, i) in recoveryCodes" :key="i"
                                              class="bg-white text-amber-900 text-xs font-mono p-2 rounded-lg border border-amber-100 select-all text-center">
                                            {{ rc }}
                                        </code>
                                    </div>
                                    <p v-else class="text-xs text-amber-600 font-bold">No recovery codes remaining. Regenerate new ones.</p>
                                </div>

                                <div class="border-t border-slate-100 pt-5">
                                    <details class="group">
                                        <summary class="text-[10px] font-black text-red-400 hover:text-red-600 uppercase cursor-pointer transition-colors list-none flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 group-open:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                            Disable Two-Factor Authentication
                                        </summary>
                                        <div class="mt-4 flex gap-3 items-start">
                                            <input v-model="disablePassword"
                                                   type="password"
                                                   autocomplete="new-password"
                                                   placeholder="Enter your password to confirm"
                                                   class="border-slate-200 px-4 py-3 rounded-xl text-sm font-bold focus:ring-2 focus:ring-red-500 outline-none bg-slate-50 w-64 transition-all" />
                                            <button @click="disable2FA"
                                                    :disabled="loading || !disablePassword"
                                                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl text-xs font-black transition-all active:scale-95 uppercase disabled:opacity-40">
                                                {{ loading ? 'Disabling...' : 'Disable' }}
                                            </button>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Management -->
                <div v-if="activeSection === 'users'">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">User Management</h2>
                                <p class="text-[9px] font-bold text-slate-400 mt-1">Manage users and their roles</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <input v-model="userSearch" placeholder="Search users..."
                                       class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/30 w-48" />
                                <button @click="openUserModal()"
                                       class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl text-[10px] font-black uppercase transition-all active:scale-95 flex items-center gap-1.5">
                                    <Plus class="w-4 h-4" /> Add User
                                </button>
                            </div>
                        </div>

                        <div v-if="usersLoading" class="flex items-center justify-center py-16">
                            <svg class="w-6 h-6 text-slate-300 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                        <div v-else class="overflow-y-auto overflow-x-hidden max-h-[calc(100vh-20rem)]">
                            <table class="w-full text-left table-fixed">
                                <thead class="sticky top-0 z-10 bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                                <tr>
                                    <th class="p-5 w-[35%]">User</th>
                                    <th class="p-5 w-[30%]">Email</th>
                                    <th class="p-5 w-[25%]">Roles</th>
                                    <th class="p-5 w-[10%] text-right">Actions</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50/30 transition-colors">
                                    <td class="p-5">
                                        <div class="flex items-center gap-3 truncate">
                                            <div class="w-9 h-9 rounded-xl shrink-0 bg-slate-800 text-white flex items-center justify-center font-black text-xs">{{ userInitials(u) }}</div>
                                            <span class="text-sm font-black text-slate-800 truncate">{{ u.name }}</span>
                                        </div>
                                    </td>
                                    <td class="p-5 text-sm font-bold text-slate-500 truncate">{{ u.email }}</td>
                                    <td class="p-5">
                                        <div class="flex flex-wrap gap-1.5 truncate">
                                            <span v-for="r in u.roles" :key="r.id"
                                                  :class="roleBadge(r.name)"
                                                  class="text-[9px] font-black px-2 py-0.5 rounded-lg uppercase shrink-0">{{ r.name }}</span>
                                        </div>
                                    </td>
                                    <td class="p-5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="openUserModal(u)" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-indigo-600 transition-colors">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <button @click="confirmDeleteUser(u)" class="p-2 rounded-xl hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!users.length">
                                    <td colspan="4" class="p-16 text-center text-slate-400 font-black uppercase tracking-widest text-xs">No users found</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Coming Soon Placeholder -->
                <div v-else-if="currentSection?.comingSoon" class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="flex flex-col items-center justify-center py-20 px-6">
                        <component :is="currentSection.icon" class="w-12 h-12 text-slate-200 mb-4" />
                        <h2 class="text-lg font-black text-slate-400 uppercase tracking-tight">{{ currentSection.label }}</h2>
                        <p class="text-xs font-bold text-slate-300 mt-2">Coming soon</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Modal -->
        <Transition name="scale-fade">
            <div v-if="showUserModal" class="fixed inset-0 z-[150] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showUserModal = false"></div>
                <div class="relative bg-white rounded-[2rem] shadow-2xl border border-slate-200 w-full max-w-lg mx-auto max-h-[90vh] overflow-y-auto overflow-x-hidden custom-scrollbar">
                    <div class="sticky top-0 z-10 bg-white border-b border-slate-100 px-8 py-5 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-200">
                            <Users class="w-5 h-5" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">{{ editingUser ? 'Edit User' : 'New User' }}</h3>
                            <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ editingUser ? 'Update user details and roles' : 'Create a new user account' }}</p>
                        </div>
                        <button @click="showUserModal = false" class="p-2 rounded-xl hover:bg-slate-100 transition-colors text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <form @submit.prevent="saveUser" class="p-8 space-y-6">
                        <div class="space-y-5">
                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2 block">Full Name</label>
                                <div class="relative">
                                    <input v-model="userForm.name" required placeholder="John Doe"
                                           class="w-full border-2 border-slate-100 bg-slate-50/50 px-4 py-3.5 rounded-xl text-sm font-bold text-slate-800 placeholder:text-slate-300 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all" />
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2 block">Email Address</label>
                                <div class="relative">
                                    <input v-model="userForm.email" type="email" required placeholder="john@example.com"
                                           class="w-full border-2 border-slate-100 bg-slate-50/50 px-4 py-3.5 rounded-xl text-sm font-bold text-slate-800 placeholder:text-slate-300 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all" />
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2 block">Password <span v-if="editingUser" class="text-slate-400 font-normal normal-case">(leave blank to keep current)</span></label>
                                <div class="relative">
                                    <input v-model="userForm.password" type="password" autocomplete="new-password"
                                           :required="!editingUser" placeholder="Minimum 8 characters"
                                           class="w-full border-2 border-slate-100 bg-slate-50/50 px-4 py-3.5 rounded-xl text-sm font-bold text-slate-800 placeholder:text-slate-300 focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all" />
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider mb-3 block">Role Assignment</label>
                                <div class="grid grid-cols-2 gap-2.5">
                                    <label v-for="role in availableRoles" :key="role"
                                           :class="[
                                               'relative flex items-center gap-3 px-4 py-3.5 rounded-xl border-2 cursor-pointer transition-all select-none',
                                               userForm.roles.includes(role)
                                                   ? 'border-indigo-400 bg-indigo-50/80 shadow-sm'
                                                   : 'border-slate-100 bg-slate-50/50 hover:border-slate-200 hover:bg-slate-50'
                                           ]">
                                        <input type="checkbox" :value="role" v-model="userForm.roles" class="hidden" />
                                        <div :class="[
                                            'w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all shrink-0',
                                            userForm.roles.includes(role)
                                                ? 'bg-indigo-600 border-indigo-600'
                                                : 'border-slate-300 bg-white'
                                        ]">
                                            <svg v-if="userForm.roles.includes(role)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <span :class="[
                                            'text-xs font-black uppercase tracking-wider',
                                            userForm.roles.includes(role) ? 'text-indigo-700' : 'text-slate-500'
                                        ]">{{ role }}</span>
                                    </label>
                                </div>
                                <p v-if="!userForm.roles.length" class="text-[10px] font-bold text-amber-500 mt-2">Select at least one role</p>
                            </div>
                        </div>
                        <div v-if="userFormError" class="flex items-center gap-2 text-red-600 text-[10px] font-bold bg-red-50/80 border border-red-100 px-4 py-3 rounded-xl">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ userFormError }}</span>
                        </div>
                        <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                            <button type="button" @click="showUserModal = false"
                                    class="px-6 py-3 rounded-xl text-[10px] font-black uppercase border-2 border-slate-200 text-slate-500 hover:bg-slate-50 hover:border-slate-300 transition-all active:scale-[0.98]">
                                Cancel
                            </button>
                            <button type="submit" :disabled="userSaving"
                                    class="px-6 py-3 rounded-xl text-[10px] font-black uppercase bg-gradient-to-br from-indigo-600 to-indigo-700 text-white hover:from-indigo-700 hover:to-indigo-800 shadow-lg shadow-indigo-200 transition-all active:scale-[0.98] disabled:opacity-40 disabled:shadow-none">
                                <span v-if="userSaving" class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Saving...
                                </span>
                                <span v-else>{{ editingUser ? 'Update' : 'Create User' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Delete Confirmation -->
        <Transition name="fade">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[160] flex items-center justify-center">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
                <div class="relative bg-white rounded-[2.5rem] shadow-2xl border border-slate-200 w-full max-w-sm mx-4 p-8 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
                        <Trash2 class="w-6 h-6" />
                    </div>
                    <h3 class="text-sm font-black text-slate-800">Delete User</h3>
                    <p class="text-xs font-bold text-slate-400 mt-2">Are you sure you want to delete <strong>{{ deletingUser?.name }}</strong>? This cannot be undone.</p>
                    <p v-if="deleteError" class="text-red-500 text-[10px] font-bold mt-3">{{ deleteError }}</p>
                    <div class="flex gap-3 justify-center mt-6">
                        <button @click="showDeleteConfirm = false"
                                class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase border border-slate-200 text-slate-500 hover:bg-slate-50 transition-all">
                            Cancel
                        </button>
                        <button @click="deleteUser" :disabled="userSaving"
                                class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase bg-rose-600 text-white hover:bg-rose-700 transition-all disabled:opacity-40">
                            {{ userSaving ? 'Deleting...' : 'Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <div v-if="notification.show" class="fixed bottom-8 right-8 z-[130] flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span class="text-sm font-bold">{{ notification.message }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { api } from '../../plugins/axios'
import { useAuthStore } from '../store/authStore'
import {
    ShieldCheck, Bell, User, Puzzle, Settings2,
    Users, Plus, Pencil, Trash2, X,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const activeSection = ref('security')

const sections = [
    {
        id: 'security',
        label: 'Security',
        description: 'Two-factor authentication & password',
        icon: ShieldCheck,
        roles: null,
    },
    {
        id: 'users',
        label: 'User Management',
        description: 'Manage users and roles',
        icon: Users,
        roles: ['super_admin', 'admin'],
    },
    {
        id: 'notifications',
        label: 'Notifications',
        description: 'Alert preferences & channels',
        icon: Bell,
        roles: null,
        comingSoon: true,
    },
    {
        id: 'profile',
        label: 'Profile',
        description: 'Personal information & contacts',
        icon: User,
        roles: null,
        comingSoon: true,
    },
    {
        id: 'integrations',
        label: 'Integrations',
        description: 'API keys & third-party services',
        icon: Puzzle,
        roles: ['super_admin', 'admin'],
        comingSoon: true,
    },
    {
        id: 'system',
        label: 'System',
        description: 'App configuration & maintenance',
        icon: Settings2,
        roles: ['super_admin'],
        comingSoon: true,
    },
]

const visibleSections = computed(() => {
    const userRoles = authStore.user?.roles_list || []
    return sections.filter(s => {
        if (!s.roles) return true
        return s.roles.some(r => userRoles.includes(r))
    })
})

const currentSection = computed(() => {
    return sections.find(s => s.id === activeSection.value)
})

// 2FA state
const twoFactorEnabled = ref(false)
const loading = ref(false)
const showSetup = ref(false)
const qrCode = ref('')
const secret = ref('')
const newRecoveryCodes = ref([])
const confirmCode = ref('')
const confirmError = ref('')
const recoveryCodes = ref([])
const showRecoveryCodes = ref(false)
const disablePassword = ref('')
const notification = ref({ show: false, message: '' })

const notify = (msg) => {
    notification.value = { show: true, message: msg }
    setTimeout(() => notification.value.show = false, 3000)
}

const checkStatus = async () => {
    try {
        const { data } = await api.get('user')
        twoFactorEnabled.value = !!data.two_factor_confirmed_at
    } catch (e) {
        console.error('Failed to check 2FA status', e)
    }
}

const enable2FA = async () => {
    loading.value = true
    try {
        const { data } = await api.post('portal/2fa/enable')
        qrCode.value = data.qr_code
        secret.value = data.secret
        newRecoveryCodes.value = data.recovery_codes
        showSetup.value = true
    } catch (e) {
        notify(e.response?.data?.message || 'Failed to enable 2FA.')
    } finally {
        loading.value = false
    }
}

const confirmSetup = async () => {
    loading.value = true
    confirmError.value = ''
    try {
        await api.post('portal/2fa/confirm', { code: confirmCode.value })
        twoFactorEnabled.value = true
        showSetup.value = false
        confirmCode.value = ''
        notify('Two-factor authentication is now active.')
    } catch (e) {
        confirmError.value = e.response?.data?.message || 'Invalid code. Try again.'
    } finally {
        loading.value = false
    }
}

const cancelSetup = () => {
    showSetup.value = false
    qrCode.value = ''
    secret.value = ''
    newRecoveryCodes.value = []
    confirmCode.value = ''
    confirmError.value = ''
}

const disable2FA = async () => {
    loading.value = true
    try {
        await api.post('portal/2fa/disable', { password: disablePassword.value })
        twoFactorEnabled.value = false
        disablePassword.value = ''
        recoveryCodes.value = []
        notify('Two-factor authentication disabled.')
    } catch (e) {
        notify(e.response?.data?.message || 'Failed to disable 2FA.')
    } finally {
        loading.value = false
    }
}

const fetchRecoveryCodes = async () => {
    try {
        const { data } = await api.get('portal/2fa/recovery-codes')
        recoveryCodes.value = data.recovery_codes || []
    } catch (e) {
        console.error('Failed to fetch recovery codes', e)
    }
}

const regenerateCodes = async () => {
    loading.value = true
    try {
        const { data } = await api.post('portal/2fa/recovery-codes/regenerate')
        recoveryCodes.value = data.recovery_codes
        notify('Recovery codes regenerated.')
    } catch (e) {
        notify(e.response?.data?.message || 'Failed to regenerate codes.')
    } finally {
        loading.value = false
    }
}

// User Management
const users = ref([])
const usersLoading = ref(false)
const userSearch = ref('')
const showUserModal = ref(false)
const showDeleteConfirm = ref(false)
const editingUser = ref(null)
const deletingUser = ref(null)
const userSaving = ref(false)
const userFormError = ref('')
const deleteError = ref('')
const availableRoles = ref([])
let searchTimer = null

const userForm = ref({
    name: '',
    email: '',
    password: '',
    roles: [],
})

watch(userSearch, (val) => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => fetchUsers(val), 300)
})

const roleBadge = (name) => {
    const map = {
        super_admin: 'bg-purple-100 text-purple-700',
        admin: 'bg-blue-100 text-blue-700',
        operator: 'bg-amber-100 text-amber-700',
        driver: 'bg-emerald-100 text-emerald-700',
        customer: 'bg-slate-100 text-slate-600',
    }
    return map[name?.toLowerCase()] || 'bg-slate-100 text-slate-600'
}

const userInitials = (u) => {
    return u.name?.substring(0, 2).toUpperCase() || '??'
}

const fetchUsers = async (search) => {
    usersLoading.value = true
    try {
        const params = search ? { search } : {}
        const { data } = await api.get('portal/users', { params })
        users.value = data.data || []
    } catch (e) {
        console.error('Failed to load users', e)
    } finally {
        usersLoading.value = false
    }
}

const fetchRoles = async () => {
    try {
        const { data } = await api.get('portal/roles')
        availableRoles.value = data || []
    } catch (e) {
        console.error('Failed to load roles', e)
    }
}

const openUserModal = (user) => {
    editingUser.value = user || null
    userForm.value = {
        name: user?.name || '',
        email: user?.email || '',
        password: '',
        roles: user?.roles?.map(r => r.name) || [],
    }
    userFormError.value = ''
    showUserModal.value = true
}

const saveUser = async () => {
    userSaving.value = true
    userFormError.value = ''
    try {
        const payload = { ...userForm.value }
        if (editingUser.value && !payload.password) {
            delete payload.password
        }
        if (editingUser.value) {
            await api.put(`portal/users/${editingUser.value.id}`, payload)
            notify('User updated.')
        } else {
            await api.post('portal/users', payload)
            notify('User created.')
        }
        showUserModal.value = false
        await fetchUsers()
    } catch (e) {
        userFormError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(', ') || 'Failed to save user.'
    } finally {
        userSaving.value = false
    }
}

const confirmDeleteUser = (user) => {
    deletingUser.value = user
    deleteError.value = ''
    showDeleteConfirm.value = true
}

const deleteUser = async () => {
    userSaving.value = true
    deleteError.value = ''
    try {
        await api.delete(`portal/users/${deletingUser.value.id}`)
        showDeleteConfirm.value = false
        notify('User deleted.')
        await fetchUsers()
    } catch (e) {
        deleteError.value = e.response?.data?.message || 'Failed to delete user.'
    } finally {
        userSaving.value = false
    }
}

onMounted(() => {
    checkStatus()
    if (authStore.user?.roles_list?.some(r => ['super_admin', 'admin'].includes(r))) {
        fetchRoles()
        fetchUsers()
    }
})
</script>

<style scoped>
.animate-spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.scale-fade-enter-active { transition: all 0.2s ease-out; }
.scale-fade-leave-active { transition: all 0.15s ease-in; }
.scale-fade-enter-from { opacity: 0; transform: scale(0.95); }
.scale-fade-leave-to { opacity: 0; transform: scale(0.95); }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
</style>
