<!DOCTYPE html>

<html class="dark" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Premium Visa Chat Wizard</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#c72929",
                        "background-light": "#f8f6f6",
                        "background-dark": "#1a1a1a",
                        "surface": "#2d1a1a",
                        "border-muted": "#432828",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "2xl": "1.25rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
<style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .glass-effect {
            background: rgba(45, 26, 26, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(199, 41, 41, 0.1);
        }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex flex-col font-display">
<!-- Header Navigation -->
<header class="sticky top-0 z-50 glass-effect border-b border-border-muted px-4 py-4 flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="size-10 rounded-full bg-primary flex items-center justify-center text-white">
<span class="material-symbols-outlined text-2xl">auto_awesome</span>
</div>
<div>
<h1 class="text-sm font-bold tracking-tight">V-PREMIUM</h1>
<p class="text-[10px] uppercase tracking-[0.2em] text-primary font-bold">Concierge AI</p>
</div>
</div>
<button class="size-10 flex items-center justify-center rounded-full hover:bg-white/5 transition-colors">
<span class="material-symbols-outlined">more_vert</span>
</button>
</header>
<main class="flex-1 overflow-y-auto px-4 py-6 space-y-8 pb-32">
<!-- Progress Indicator -->
<div class="space-y-3">
<div class="flex justify-between items-end">
<span class="text-[11px] font-bold uppercase tracking-widest text-primary">Application Progress</span>
<span class="text-xs font-medium text-slate-400">Step 1 of 4</span>
</div>
<div class="h-1.5 w-full bg-border-muted rounded-full overflow-hidden">
<div class="h-full bg-primary rounded-full transition-all duration-1000" style="width: 25%;"></div>
</div>
<div class="flex gap-4 overflow-x-auto no-scrollbar py-1">
<span class="text-[12px] font-bold text-white shrink-0">Personal Info</span>
<span class="text-[12px] font-medium text-slate-500 shrink-0">Visa Selection</span>
<span class="text-[12px] font-medium text-slate-500 shrink-0">Documents</span>
<span class="text-[12px] font-medium text-slate-500 shrink-0">Payment</span>
</div>
</div>
<!-- Chat Interaction Area -->
<div class="space-y-6">
<!-- Assistant Message -->
<div class="flex gap-3 max-w-[85%]">
<div class="size-8 rounded-lg bg-primary/20 flex items-center justify-center shrink-0 border border-primary/30">
<span class="material-symbols-outlined text-primary text-sm">smart_toy</span>
</div>
<div class="space-y-2">
<div class="bg-surface p-4 rounded-2xl rounded-tl-none border border-border-muted shadow-xl">
<p class="text-sm leading-relaxed text-slate-100">
                            Welcome to the <span class="text-primary font-bold">Elite Visa Concierge</span>. I will be your dedicated assistant through this process. 
                        </p>
<p class="text-sm mt-2 leading-relaxed text-slate-300">
                            To provide the most tailored experience, please select the type of visa you are interested in:
                        </p>
</div>
<span class="text-[10px] text-slate-500 ml-1">09:41 AM</span>
</div>
</div>
<!-- Visa Selection Menu (The interactive part) -->
<div class="pl-11 space-y-3">
<!-- Option 1: Tourist -->
<button class="w-full group flex items-center justify-between p-4 bg-surface/50 hover:bg-primary/10 border border-border-muted hover:border-primary/50 rounded-2xl transition-all duration-300">
<div class="flex items-center gap-4">
<div class="size-12 rounded-xl bg-background-dark flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined">map</span>
</div>
<div class="text-left">
<h3 class="font-bold text-sm">Tourist Visa</h3>
<p class="text-[11px] text-slate-500">For leisure and sightseeing travel</p>
</div>
</div>
<span class="material-symbols-outlined text-slate-600 group-hover:text-primary transition-colors">chevron_right</span>
</button>
<!-- Option 2: Business -->
<button class="w-full group flex items-center justify-between p-4 bg-surface/50 hover:bg-primary/10 border border-border-muted hover:border-primary/50 rounded-2xl transition-all duration-300">
<div class="flex items-center gap-4">
<div class="size-12 rounded-xl bg-background-dark flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined">work</span>
</div>
<div class="text-left">
<h3 class="font-bold text-sm">Business Visa</h3>
<p class="text-[11px] text-slate-500">Conferences, meetings, and trade</p>
</div>
</div>
<span class="material-symbols-outlined text-slate-600 group-hover:text-primary transition-colors">chevron_right</span>
</button>
<!-- Option 3: Student -->
<button class="w-full group flex items-center justify-between p-4 bg-surface/50 hover:bg-primary/10 border border-border-muted hover:border-primary/50 rounded-2xl transition-all duration-300">
<div class="flex items-center gap-4">
<div class="size-12 rounded-xl bg-background-dark flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined">school</span>
</div>
<div class="text-left">
<h3 class="font-bold text-sm">Student Visa</h3>
<p class="text-[11px] text-slate-500">Degree programs and short courses</p>
</div>
</div>
<span class="material-symbols-outlined text-slate-600 group-hover:text-primary transition-colors">chevron_right</span>
</button>
</div>
</div>
</main>
<!-- Bottom Action Area -->
<nav class="fixed bottom-0 w-full glass-effect border-t border-border-muted pb-8 pt-4 px-6 flex items-center gap-4">
<div class="flex-1 relative">
<input class="w-full bg-background-dark border border-border-muted rounded-full px-5 py-3 text-sm focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all placeholder:text-slate-600" placeholder="Type your response..." type="text"/>
<button class="absolute right-2 top-1/2 -translate-y-1/2 size-8 bg-primary rounded-full flex items-center justify-center text-white shadow-lg shadow-primary/20">
<span class="material-symbols-outlined text-lg">arrow_upward</span>
</button>
</div>
<button class="size-12 bg-surface border border-border-muted rounded-full flex items-center justify-center text-slate-400">
<span class="material-symbols-outlined">mic</span>
</button>
</nav>
<!-- Navigation Bar Component (Hidden but structured) -->
<div class="fixed bottom-0 left-0 right-0 hidden">
<div class="flex gap-2 border-t border-border-muted bg-surface px-4 pb-3 pt-2">
<a class="flex flex-1 flex-col items-center justify-end gap-1 text-slate-500" href="#">
<span class="material-symbols-outlined">history</span>
<p class="text-[10px] font-medium leading-normal">Status</p>
</a>
<a class="flex flex-1 flex-col items-center justify-end gap-1 text-primary" href="#">
<span class="material-symbols-outlined">description</span>
<p class="text-[10px] font-medium leading-normal">Apply</p>
</a>
<a class="flex flex-1 flex-col items-center justify-end gap-1 text-slate-500" href="#">
<span class="material-symbols-outlined">folder</span>
<p class="text-[10px] font-medium leading-normal">Docs</p>
</a>
<a class="flex flex-1 flex-col items-center justify-end gap-1 text-slate-500" href="#">
<span class="material-symbols-outlined">support_agent</span>
<p class="text-[10px] font-medium leading-normal">Support</p>
</a>
</div>
</div>
</body></html>