
<div class="bg-white dark:bg-slate-800 shadow rounded-lg p-6">
    <h1 class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-4">
        <?= htmlspecialchars($pageTitle ?? 'Home') ?>
    </h1>

    <p class="text-lg text-gray-700 dark:text-gray-300">
        <?= htmlspecialchars($message ?? 'Welcome!') ?>
    </p>

    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
        This is the home page content. Tailwind CSS v4 should be working!
    </p>

     <div class="mt-6 p-4 border border-dashed border-gray-400 dark:border-gray-600 rounded">
         <h2 class="font-semibold mb-2">Tailwind Test:</h2>
        <button class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded mr-2 transition-colors duration-200">
            Purple Button
        </button>
         <span class="bg-yellow-200 text-yellow-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300">Yellow Badge</span>
         <div class="mt-4 p-4 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg text-white">
             Gradient Box
         </div>
     </div>

</div>