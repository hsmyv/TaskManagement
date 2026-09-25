function taskCalendar() {
    return {
        current: new Date(),
        selectedDate: '',
        tasks: [],
        weekDays: ['B.e', 'Ç.a', 'Ç', 'C.a', 'C', 'Ş', 'B'],

        async init() {
            this.current = new Date(this.current.getFullYear(), this.current.getMonth(), 1);
            this.selectedDate = this.formatDateKey(new Date());
            await this.loadTasks();
        },

        get monthTitle() {
            return this.current.toLocaleDateString('az-AZ', { month: 'long', year: 'numeric' });
        },

        get calendarDays() {
            const first = new Date(this.current.getFullYear(), this.current.getMonth(), 1);
            const startOffset = (first.getDay() + 6) % 7;
            const start = new Date(first);
            start.setDate(first.getDate() - startOffset);

            return Array.from({ length: 42 }, (_, index) => {
                const date = new Date(start);
                date.setDate(start.getDate() + index);
                return {
                    date,
                    key: this.formatDateKey(date),
                    inMonth: date.getMonth() === this.current.getMonth(),
                    isToday: this.formatDateKey(date) === this.formatDateKey(new Date()),
                };
            });
        },

        get selectedTasks() {
            return this.tasks.filter(task => task.due_date === this.selectedDate);
        },

        get selectedDateLabel() {
            if (!this.selectedDate) return 'Gün seçilməyib';
            return new Date(`${this.selectedDate}T12:00:00`).toLocaleDateString('az-AZ', {
                day: 'numeric', month: 'long', year: 'numeric'
            });
        },

        async loadTasks() {
            const firstDay = new Date(this.current.getFullYear(), this.current.getMonth(), 1);
            const lastDay = new Date(this.current.getFullYear(), this.current.getMonth() + 1, 0);
            const params = new URLSearchParams({
                from: this.formatDateKey(firstDay),
                to: this.formatDateKey(lastDay),
            });
            const response = await api('GET', `/tasks/calendar?${params.toString()}`);
            this.tasks = Array.isArray(response) ? response : (response.data || []);
        },

        async previousMonth() {
            this.current = new Date(this.current.getFullYear(), this.current.getMonth() - 1, 1);
            await this.loadTasks();
        },

        async nextMonth() {
            this.current = new Date(this.current.getFullYear(), this.current.getMonth() + 1, 1);
            await this.loadTasks();
        },

        async goToday() {
            this.current = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
            this.selectedDate = this.formatDateKey(new Date());
            await this.loadTasks();
        },

        tasksFor(day) {
            return this.tasks.filter(task => task.due_date === day.key);
        },

        formatDateKey(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },

        priorityClass(task) {
            if (task.is_overdue) return 'border-red-200 bg-red-50 text-red-800';
            return {
                urgent: 'border-red-200 bg-red-50 text-red-800',
                high: 'border-orange-200 bg-orange-50 text-orange-800',
                medium: 'border-blue-200 bg-blue-50 text-blue-800',
                low: 'border-slate-200 bg-slate-50 text-slate-700',
            }[task.priority] || 'border-slate-200 bg-slate-50 text-slate-700';
        },

        statusClass(status) {
            return {
                todo: 'bg-slate-100 text-slate-600',
                in_progress: 'bg-blue-100 text-blue-700',
                waiting_for_approve: 'bg-amber-100 text-amber-700',
                completed: 'bg-green-100 text-green-700',
                canceled: 'bg-red-100 text-red-700',
            }[status] || 'bg-slate-100 text-slate-600';
        },
    };
}
