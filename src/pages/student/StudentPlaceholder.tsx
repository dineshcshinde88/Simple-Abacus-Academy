import StudentLayout from "@/layouts/StudentLayout";

type StudentPlaceholderProps = {
  title: string;
  subtitle?: string;
};

const StudentPlaceholder = ({ title, subtitle }: StudentPlaceholderProps) => (
  <StudentLayout
    header={(
      <div>
        <h1 className="text-2xl md:text-3xl font-heading font-bold text-slate-900">{title}</h1>
        {subtitle && <p className="text-sm text-slate-500 mt-1">{subtitle}</p>}
      </div>
    )}
  >
    <div className="bg-white rounded-2xl shadow-card p-6">
      <p className="text-slate-600">This section will be updated with live data soon.</p>
    </div>
  </StudentLayout>
);

export default StudentPlaceholder;
