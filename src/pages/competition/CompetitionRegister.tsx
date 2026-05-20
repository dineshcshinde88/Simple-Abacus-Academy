import { FormEvent, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/hooks/use-toast";
import { competitionRegister } from "@/services/competitionApi";

const CompetitionRegister = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [submitting, setSubmitting] = useState(false);
  const [form, setForm] = useState({
    name: "",
    email: "",
    mobile: "",
    city: "",
    school: "",
    gender: "",
    dateOfBirth: "",
    maatsCategory: "",
    maatsSubcategory: "",
    calculusGrade: "",
    streetAddress: "",
    state: "",
    pinCode: "",
    country: "India",
  });

  const update = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!form.name.trim() || !form.email.trim()) {
      toast({ title: "Missing details", description: "Name and email are required." });
      return;
    }

    try {
      setSubmitting(true);
      await competitionRegister({
        name: form.name.trim(),
        email: form.email.trim(),
        mobile: form.mobile.trim(),
        city: form.city.trim(),
        school: form.school.trim(),
        gender: form.gender.trim(),
        dateOfBirth: form.dateOfBirth,
        maatsCategory: form.maatsCategory.trim(),
        maatsSubcategory: form.maatsSubcategory.trim(),
        calculusGrade: form.calculusGrade.trim(),
        streetAddress: form.streetAddress.trim(),
        state: form.state.trim(),
        pinCode: form.pinCode.trim(),
        country: form.country.trim(),
      });
      toast({
        title: "Competition registration submitted",
        description: "Admin approval is required. Login credentials will be shared after approval.",
      });
      navigate("/online-competition");
    } catch (error) {
      toast({ title: "Registration failed", description: error instanceof Error ? error.message : "Please try again." });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#eef4ff] px-4 py-8">
      <div className="mx-auto flex min-h-[calc(100vh-4rem)] max-w-5xl flex-col justify-center">
        <form onSubmit={handleSubmit} className="rounded-xl bg-white p-6 shadow-lg sm:p-8">
          <div className="text-center">
            <h1 className="text-2xl font-bold tracking-normal text-slate-950">Online Competition Registration</h1>
            <p className="mt-3 text-sm text-slate-500">Create a separate competition portal account request.</p>
          </div>

          <div className="mt-8 grid gap-5 md:grid-cols-2">
            <div className="space-y-2">
              <Label>Full Name</Label>
              <Input value={form.name} onChange={(event) => update("name", event.target.value)} placeholder="Student full name" />
            </div>
            <div className="space-y-2">
              <Label>Email</Label>
              <Input type="email" value={form.email} onChange={(event) => update("email", event.target.value)} placeholder="student@example.com" />
            </div>
            <div className="space-y-2">
              <Label>Mobile Number</Label>
              <Input value={form.mobile} onChange={(event) => update("mobile", event.target.value)} placeholder="+91 99999 99999" />
            </div>
            <div className="space-y-2">
              <Label>School / Institute</Label>
              <Input value={form.school} onChange={(event) => update("school", event.target.value)} placeholder="School name" />
            </div>
            <div className="space-y-2">
              <Label>Gender</Label>
              <select
                value={form.gender}
                onChange={(event) => update("gender", event.target.value)}
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              >
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label>Date of Birth</Label>
              <Input type="date" value={form.dateOfBirth} onChange={(event) => update("dateOfBirth", event.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Simple Abacus Category</Label>
              <Input value={form.maatsCategory} onChange={(event) => update("maatsCategory", event.target.value)} placeholder="Category B" />
            </div>
            <div className="space-y-2">
              <Label>Simple Abacus Subcategory</Label>
              <Input value={form.maatsSubcategory} onChange={(event) => update("maatsSubcategory", event.target.value)} placeholder="Junior (Age 8-9)" />
            </div>
            <div className="space-y-2">
              <Label>Calculus Grade</Label>
              <Input value={form.calculusGrade} onChange={(event) => update("calculusGrade", event.target.value)} placeholder="Grade 3" />
            </div>
          </div>

          <div className="mt-7">
            <h2 className="text-lg font-bold text-slate-950">Address</h2>
            <div className="mt-4 grid gap-5 md:grid-cols-2">
              <div className="space-y-2 md:col-span-2">
                <Label>Street Address</Label>
                <Input value={form.streetAddress} onChange={(event) => update("streetAddress", event.target.value)} placeholder="House no, society, area" />
              </div>
              <div className="space-y-2">
                <Label>City</Label>
                <Input value={form.city} onChange={(event) => update("city", event.target.value)} placeholder="City" />
              </div>
              <div className="space-y-2">
                <Label>State</Label>
                <Input value={form.state} onChange={(event) => update("state", event.target.value)} placeholder="State" />
              </div>
              <div className="space-y-2">
                <Label>Pin Code</Label>
                <Input value={form.pinCode} onChange={(event) => update("pinCode", event.target.value)} placeholder="412307" />
              </div>
              <div className="space-y-2">
                <Label>Country</Label>
                <Input value={form.country} onChange={(event) => update("country", event.target.value)} placeholder="India" />
              </div>
            </div>
          </div>

          <Button type="submit" className="mt-7 h-11 w-full rounded-md bg-blue-600 hover:bg-blue-700" disabled={submitting}>
            {submitting ? "Submitting..." : "Submit Registration"}
          </Button>
        </form>

        <p className="mt-7 text-center text-sm text-slate-500">
          Already registered?{" "}
          <Link to="/online-competition" className="font-semibold text-blue-600">
            Sign in
          </Link>
        </p>
      </div>
    </div>
  );
};

export default CompetitionRegister;
