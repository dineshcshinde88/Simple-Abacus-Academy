import { useState } from "react";
import { Link } from "react-router-dom";
import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import { registerInstructor } from "@/services/instructorAuthApi";

const InstructorRegistration = () => {
  const [form, setForm] = useState({
    courseType: "abacus",
    fullName: "",
    countryCode: "+91",
    mobile: "",
    email: "",
    gender: "",
    dateOfBirth: "",
    qualification: "",
    experience: "",
    careerStarted: "",
    studentsTrained: "",
    address: "",
    password: "",
    confirmPassword: "",
  });
  const [profilePicture, setProfilePicture] = useState<File | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);

  const updateField = (field: keyof typeof form, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const updateProfilePicture = (file: File | null) => {
    if (file && file.size > 2 * 1024 * 1024) {
      setProfilePicture(null);
      toast.error("Profile picture must be 2MB or smaller.");
      return;
    }
    setProfilePicture(file);
  };

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (
      !form.courseType ||
      !form.fullName.trim() ||
      !form.mobile.trim() ||
      !form.email.trim() ||
      !form.gender ||
      !form.dateOfBirth ||
      !form.qualification.trim() ||
      !form.experience.trim() ||
      !form.careerStarted.trim() ||
      !form.studentsTrained.trim() ||
      !form.address.trim() ||
      !form.password ||
      !form.confirmPassword
    ) {
      toast.error("Please fill all fields.");
      return;
    }
    if (form.password !== form.confirmPassword) {
      toast.error("Passwords do not match.");
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await registerInstructor({
        courseType: form.courseType,
        fullName: form.fullName.trim(),
        countryCode: form.countryCode,
        mobile: form.mobile.trim(),
        email: form.email.trim(),
        gender: form.gender,
        dateOfBirth: form.dateOfBirth,
        qualification: form.qualification.trim(),
        experience: form.experience.trim(),
        careerStarted: form.careerStarted.trim(),
        studentsTrained: form.studentsTrained.trim(),
        address: form.address.trim(),
        profilePicture,
        password: form.password,
        confirmPassword: form.confirmPassword,
      });
      toast.success(response.message || "Registration submitted successfully. Wait for admin approval.");
      setIsSubmitted(true);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Unable to submit registration.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <main className="pt-16">
        <section className="py-16">
          <div className="container mx-auto px-4">
            <div className="mx-auto max-w-4xl rounded-2xl border border-border bg-white p-8 shadow-card">
              <div className="text-center">
                <img src="/abacus_logo.png" alt="Simple Abacus" className="mx-auto h-16 w-auto" />
                <h1 className="mt-6 text-3xl font-heading font-bold text-[#4B1E83]">Instructor Registration</h1>
                <p className="mt-2 text-sm text-muted-foreground">Create your instructor account and wait for admin approval.</p>
              </div>

              <form onSubmit={handleSubmit} className="mt-8 grid gap-5 md:grid-cols-2">
                {isSubmitted && (
                  <div className="rounded-md border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800 md:col-span-2">
                    Your tutor application has been submitted to admin. You can login after admin approval.
                  </div>
                )}
                <div className="space-y-2 md:col-span-2">
                  <Label>Course Type</Label>
                  <div className="grid gap-3 sm:grid-cols-2">
                    {[
                      { value: "abacus", label: "Abacus" },
                      { value: "vedic_maths", label: "Vedic Maths" },
                    ].map((option) => (
                      <button
                        key={option.value}
                        type="button"
                        onClick={() => updateField("courseType", option.value)}
                        className={`h-11 rounded-md border text-sm font-semibold transition ${
                          form.courseType === option.value
                            ? "border-[#4B1E83] bg-[#4B1E83] text-white"
                            : "border-input bg-background text-foreground hover:border-[#4B1E83]"
                        }`}
                      >
                        {option.label}
                      </button>
                    ))}
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="fullName">Full Name</Label>
                  <Input
                    id="fullName"
                    placeholder="Enter full name"
                    value={form.fullName}
                    onChange={(event) => updateField("fullName", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="mobile">Mobile Number</Label>
                  <div className="flex gap-2">
                    <Select value={form.countryCode} onValueChange={(value) => updateField("countryCode", value)}>
                      <SelectTrigger className="w-32">
                        <SelectValue placeholder="Code" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="+91">IN +91</SelectItem>
                        <SelectItem value="+1">US +1</SelectItem>
                        <SelectItem value="+44">UK +44</SelectItem>
                        <SelectItem value="+971">UAE +971</SelectItem>
                        <SelectItem value="+61">AU +61</SelectItem>
                        <SelectItem value="+65">SG +65</SelectItem>
                      </SelectContent>
                    </Select>
                    <Input
                      id="mobile"
                      placeholder="Enter mobile number"
                      value={form.mobile}
                      onChange={(event) => updateField("mobile", event.target.value)}
                      required
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="email">Email ID</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="Enter email"
                    value={form.email}
                    onChange={(event) => updateField("email", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="gender">Gender</Label>
                  <Select value={form.gender} onValueChange={(value) => updateField("gender", value)}>
                    <SelectTrigger id="gender">
                      <SelectValue placeholder="Select Gender" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="male">Male</SelectItem>
                      <SelectItem value="female">Female</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="dateOfBirth">Date of Birth</Label>
                  <Input
                    id="dateOfBirth"
                    type="date"
                    value={form.dateOfBirth}
                    onChange={(event) => updateField("dateOfBirth", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="qualification">Qualification</Label>
                  <Input
                    id="qualification"
                    placeholder="Highest qualification"
                    value={form.qualification}
                    onChange={(event) => updateField("qualification", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="experience">Experience</Label>
                  <Input
                    id="experience"
                    placeholder="Example: 5+ years experience"
                    value={form.experience}
                    onChange={(event) => updateField("experience", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="careerStarted">Career Started</Label>
                  <Input
                    id="careerStarted"
                    placeholder="Example: 2021 or 3 years ago"
                    value={form.careerStarted}
                    onChange={(event) => updateField("careerStarted", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="studentsTrained">Students Trained</Label>
                  <Input
                    id="studentsTrained"
                    inputMode="numeric"
                    placeholder="Number of students trained"
                    value={form.studentsTrained}
                    onChange={(event) => updateField("studentsTrained", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="profilePicture">Upload Profile Picture</Label>
                  <Input
                    id="profilePicture"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    onChange={(event) => updateProfilePicture(event.target.files?.[0] ?? null)}
                  />
                  <p className="text-xs text-muted-foreground">JPG, PNG or WebP, maximum 2MB.</p>
                </div>
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="address">Address</Label>
                  <Textarea
                    id="address"
                    placeholder="Enter full address"
                    rows={4}
                    value={form.address}
                    onChange={(event) => updateField("address", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="password">Password</Label>
                  <Input
                    id="password"
                    type="password"
                    placeholder="Create password"
                    value={form.password}
                    onChange={(event) => updateField("password", event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="confirmPassword">Confirm Password</Label>
                  <Input
                    id="confirmPassword"
                    type="password"
                    placeholder="Confirm password"
                    value={form.confirmPassword}
                    onChange={(event) => updateField("confirmPassword", event.target.value)}
                    required
                  />
                </div>
                <Button type="submit" className="w-full bg-[#4B1E83] hover:bg-[#3c176a] md:col-span-2" disabled={isSubmitting}>
                  {isSubmitting ? "Submitting..." : "Submit Registration"}
                </Button>
              </form>

              <p className="mt-6 text-center text-sm text-muted-foreground">
                Already registered?{" "}
                <Link to="/instructor-login" className="font-semibold text-orange-600 hover:underline">
                  Login
                </Link>
              </p>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default InstructorRegistration;
