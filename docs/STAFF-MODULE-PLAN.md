# Staff — how it should work

**Written for:** the person running the school, not the person writing the code.
There is no technical jargon in Parts 1 to 10. Part 11 onward is for the
developer.

**Date:** 2026-09-08 · **Status:** proposal, nothing built yet

---

## Part 1 — The one idea that matters

A school has **people**, and a school has **jobs**. They are not the same thing,
and almost every problem in a staff system comes from confusing the two.

Think of Akram sahib. He drives the school van in the morning and afternoon. In
between, he runs errands to the bank and the stationers — peon duty. He is **one
person** doing **two jobs**.

Most school software makes you choose. You put him down as "Driver" and then his
peon work is invisible: he is not on the peon duty roster, his errand hours are
not counted, and when the office asks "how many peons do we have", he is not one
of them. Or you create him twice — once as a driver, once as a peon — and now he
has two employee numbers, two salaries, and two attendance records for the same
man.

**Our rule: one person, one record, many jobs.**

Akram sahib has one file in the system. Attached to that file are two job
assignments: Driver (his main one) and Peon. One salary. One attendance record.
One phone number. But he appears on both duty lists, and both departments can
see him.

This is not a rare case. In a Pakistani school it is the normal case:

- The aya who also supervises the nursery class at break.
- The chowkidar who is also the mali.
- The clerk who also collects fee at the counter.
- The teacher who is also the librarian, or the exam coordinator.
- The head teacher who still teaches four periods a day.

If you take away one idea from this document, take that one.

---

## Part 2 — Who works in a school here

The system should come ready with these, and let you add your own.

### Teaching

| Job | What it means here |
|---|---|
| **Teacher** | Teaches specific subjects to specific classes |
| **Class teacher** | A teacher who "owns" one section — its register, its result, its parents |
| **Head teacher / Incharge** | Runs a wing or a section of the school |
| **Coordinator / HOD** | Runs a subject across classes, or a whole level (primary, middle) |
| **Visiting / part-time teacher** | Paid by the period or by the month, not full time |
| **Substitute teacher** | Covers for absent teachers |
| **Lab assistant** | Science or computer lab |
| **Librarian** | Often a teacher wearing a second hat |

### Office and administration

Principal · Vice principal · Campus admin · Accountant · Clerk · Receptionist ·
Admission officer · IT / computer operator · Store keeper

### Support staff

Naib qasid (peon) · Aya / maid · Sweeper · Chowkidar (guard) · Mali (gardener) ·
Cook · Van driver · Van conductor / helper

**Why this list matters:** the support staff are usually the ones a school
system forgets, and they are the ones whose salary is most often paid in cash,
whose attendance is least recorded, and whose absence causes the most trouble on
the day. They belong in the system exactly like a teacher does.

---

## Part 3 — What we keep about everybody

The same file for every member of staff, teacher or chowkidar.

### Who they are

- Full name, and father's or husband's name
- **CNIC** — this is the one thing that must never be repeated. Two files with
  the same CNIC means the same person entered twice, and the system must refuse
  it outright.
- Date of birth, gender, marital status
- Religion — needed on some government forms
- Blood group — needed the day there is an accident
- Photograph

### How to reach them

- Mobile number (and a second one — the number that works when the first does not)
- WhatsApp number, if different
- Home address, and permanent address if they are from another city
- **Emergency contact**: a name, a relationship and a number. This is not
  paperwork. It is the number you ring at 11 in the morning when someone
  collapses.

### Their job

- Employee number — issued once, never changed, printed on the salary slip
- Which campus, and which department
- **Their jobs** — see Part 4
- Date of joining
- Type: permanent, on contract, probation, part-time, visiting, daily wage
- Date of confirmation, if they were on probation
- Reporting to — which member of staff is their senior

### What they studied

Matric · FA / FSc · BA / BSc · MA / MSc · MPhil · **B.Ed / M.Ed** · PTC / CT ·
any diploma or certificate

For a teacher this is not decoration. B.Ed is asked for by the education
department during inspection, and by parents at admission time.

### Their history before you

Previous school or employer, the years, the post held, and the reason for
leaving. Useful at appraisal time and essential when the education department
asks for a staff profile.

---

## Part 4 — Jobs, and why a person may have several

Every member of staff has **one or more job assignments**. Each assignment
records:

- **Which job** — Driver, Teacher, Aya, Clerk…
- **Which campus** — a person can work at two campuses
- **From when, and until when** — so history survives (Part 9)
- **Is it their main job?** — exactly one is marked main

### Why "main job" matters

The main job decides three things and nothing else:

1. Which salary scale they sit on
2. Which screen they land on when they log in
3. How they appear in a list — "Akram (Driver)", not "Akram (Driver, Peon)"

Everything else follows all their jobs. Akram appears on the driver roster
**and** the peon roster. The transport incharge sees him, and so does the office
manager.

### What a job assignment gives them in the system

Each job carries its own access. A teacher sees their classes. A driver sees
their route. Give Akram both jobs and he sees both — not because someone
remembered to tick extra boxes, but because that is what the jobs mean.

When a job ends, the access ends with it. The teacher who stops being exam
coordinator in April stops seeing the exam screens in April, and nobody has to
remember to take it away.

### Real examples

| Person | Main job | Also | What they see |
|---|---|---|---|
| Akram | Driver | Peon | Their van route, and the errand roster |
| Nasreen | Aya | Nursery assistant | The nursery class list |
| Bilal | Teacher | Librarian | Their classes, and the library |
| Farhat | Head teacher | Teacher | Her wing, and her own four periods |
| Zahid | Chowkidar | Mali | The duty roster |

---

## Part 5 — Teachers

A teacher is a member of staff like anyone else — everything in Part 3 applies.
What follows is what a teacher needs **in addition**, and it is the largest part
of this module because it is the part the school runs on.

### What a teacher is attached to

**Subjects.** Which subjects they are qualified and permitted to teach. Not
which they happen to teach this year — that changes; this is the list of what
they *can* be given.

**Classes and sections.** Which class and which section, for which subject, in
which session. Sadia teaches Maths to 6-A and 6-B, and Science to 6-A. That is
three assignments, not one.

**Class teacher.** One section, and one teacher for it. The class teacher takes
that section's register, hands out its result cards, and is the first person a
parent asks for. This must be a single, unambiguous name — not "one of the
teachers of 6-A".

**Workload.** How many periods a week they are actually teaching. The office
needs to see this before it hands out one more class, because "who is free on
Tuesday third period" is a question asked every single day.

### Why this connects to what we have already built

The attendance module is finished and it has one gap left, which we could not
close: **the system does not know which class a teacher owns**, so it cannot
stop a teacher opening another class's register. Everything in that module is
built and waiting for this one fact.

Once a teacher is attached to their classes, that gap closes by itself: a
teacher sees their own sections and nobody else's, and a head teacher sees their
whole wing.

### What a teacher also does, beyond teaching

- **Exam duties** — setting papers, invigilating, marking. Schools rotate these
  and keep a record, because "you did it last time" is an argument that needs
  evidence.
- **Substitute duty** — covering an absent colleague. Worth recording: at the
  end of the month a teacher who covered eleven periods for others has a fair
  claim to be heard.
- **Parent meetings** — which PTM they attended, and for which section.
- **Training** — courses attended, certificates earned. The education department
  asks for this.

### Vacations

Teaching staff usually get summer and winter vacation; support staff usually do
not. That difference has to be a setting, not an assumption, because schools
split on it: some pay full salary through the vacation, some pay half, some keep
a duty roster running through it and pay those on duty extra.

---

## Part 6 — Money

This is where a system built abroad fails a Pakistani school, because it assumes
salary is one number. Here it never is.

### The salary slip

A salary here is built up, not stated:

**Earnings**
- Basic salary
- House rent allowance
- Conveyance allowance
- Medical allowance
- Utility allowance
- Qualification or special allowance
- Overtime, or extra periods taught

**Deductions**
- Income tax
- EOBI contribution
- Provident fund
- Instalment against an advance or loan
- Absence or late-arrival deduction
- Any fine

**Net** — what actually reaches their hand.

The system must let a school define these heads itself. One school pays a
"transport allowance", another calls it "conveyance", a third adds a "Ramzan
allowance" in the ninth month. All of them are right, and none of them should
need a programmer.

### Advances and loans

Extremely common, and almost always handled on a piece of paper in a drawer. A
member of staff asks for Rs 20,000 in advance and it comes back at Rs 4,000 a
month over five months.

The system should hold: what was given, when, how much comes back each month,
how much is still owed. And it should stop somebody being given a second advance
while the first is only half returned — or at least show the person approving it
what is already outstanding.

### How they are paid

Bank transfer, cheque or cash. Cash is still normal for support staff and there
is no point pretending otherwise: the system should record it as cash, print a
receipt, and take a signature.

### The monthly run

Once a month the office produces the salaries. The system should:

1. Take everyone's salary structure
2. Apply this month's absences, late arrivals and overtime
3. Apply this month's advance instalments
4. Show the whole list **before** anything is final, so it can be checked
5. Let it be approved, and only then locked
6. Print a salary slip for each person and a bank letter for the whole run

**Locked means locked.** Once a month's payroll is approved, it does not change.
If something was wrong, it is corrected in the next month with a visible
adjustment line — never by quietly editing a month that has already been paid.
This is the same rule we applied to the attendance register, for the same
reason: a record somebody has acted on cannot be rewritten behind them.

---

## Part 7 — Staff attendance and leave

### Attendance

The same daily question as for students — present, absent, on leave, late — but
with a difference that matters: **the time**. A teacher who arrives at 8:40 for
an 8:00 start is present, and also late, and after enough of those it becomes a
salary matter.

So: check-in and check-out, and a rule for what counts as late. That rule must
be a setting per campus, because it moves — school starts an hour earlier in
Ramzan, and later in winter in the north.

Most schools of any size already have a thumb scanner at the gate. The system
should be able to take that machine's records in, rather than making somebody
type them.

### Leave

The kinds actually used here:

| Kind | Notes |
|---|---|
| Casual | A fixed number a year, usually 10–15 |
| Sick | Medical certificate beyond two or three days |
| Earned / annual | Often can be accumulated or encashed |
| Maternity | Paid, and the law sets the length |
| Paternity | Newer, but increasingly given |
| Hajj / Umrah | Usually unpaid, usually granted |
| Without pay | Everything else |

Each kind needs: how many a year, whether it is paid, whether it can be carried
into next year, and who approves it. A teacher's casual leave might be approved
by the head teacher; a month without pay by the principal.

And leave must connect to attendance and to payroll by itself. An approved leave
should mean the register does not mark them absent, and unpaid leave should
reach the salary without anyone re-typing it.

---

## Part 8 — Documents

For every member of staff, held against their file:

CNIC copy · Photograph · Educational certificates · Experience letters ·
Appointment letter · Contract · Police character certificate · Medical fitness ·
Bank account details

**For drivers, additionally and without exception:** driving licence with its
category (LTV or HTV) **and its expiry date**.

That expiry date is the single most important date in this module. A school van
carrying forty children driven by a man whose licence lapsed two months ago is
not a paperwork problem; it is the thing that ends a school. The system must
warn before it expires, not after.

The same applies to any document with an expiry: contracts, medical
certificates, police verification.

---

## Part 9 — Joining, leaving, and coming back

Staff leave. Some come back. The system must not lose either fact.

**Joining** — date, post, campus, salary agreed, appointment letter.

**Changes during service** — promotion, transfer to another campus, salary
revision, change of job. Each one is a **new entry in their history**, not an
overwrite. When somebody asks "what was Sadia's salary in 2024", the system must
be able to answer, and it can only answer if the old figure was never painted
over.

This is exactly how we handled students: when a child changes class, we close
one period and open the next, so five years later we can still say where they
were and what they were charged. Staff work the same way.

**Leaving** — date, reason (resigned, contract ended, terminated, retired),
whether they can be re-hired, final settlement, and the experience letter.

**Coming back** — a returning member of staff is the **same person**, matched on
their CNIC. Same file, same history, a new period of service. They should not be
entered afresh as though the school had never met them.

---

## Part 10 — Who sees what

| Who | Sees |
|---|---|
| Owner | Everything, every campus |
| Principal / Campus admin | Their campus: everybody's file, salary, attendance |
| Head teacher | Their wing's teachers — attendance and workload, **not** salary |
| Accountant | Salary and payroll for everyone; not personal files |
| Teacher | Their own file, their own salary slips, their own classes |
| Support staff | Their own file, their own salary slips, their own duty |

Two rules worth stating plainly:

**Salary is private.** A teacher sees their own salary slip and nobody else's. A
head teacher sees who was absent, not what they are paid. Only the accountant,
the principal and the owner see the whole payroll.

**A staff member may also be a parent.** Very common — their child studies at
the same school, often on a staff concession, which the fee module already
supports. That is one person with one login who sees both their own salary slip
and their child's fee voucher. Not two accounts.

---

## Part 11 — What exists today, and what has to change

*This part is for the developer.*

### Already built

| Table | Holds |
|---|---|
| `staff_departments` | Departments |
| `staff_designations` | Job titles |
| `staff_profiles` | employee_no, campus, department, **designation_id**, employment_type, hire_date, confirmation_date, basic_salary, allowance_amount, deduction_amount, payment method, bank details |
| `payroll_runs` | A month's payroll |
| `payroll_run_items` | One person's line in it |

Thirteen roles are already seeded, including `driver`, `clerk`, `maid` and
`receptionist`, and the permission system (Spatie) is in place and working.

### The four things that must change

**1. `designation_id` is singular — this is the blocking problem.**
`staff_profiles` allows one designation per person, which makes Part 1
impossible. It needs a `staff_role_assignments` table: staff, designation,
campus, from, to, `is_primary`. Everything else in this document depends on that
change, so it goes first.

**2. `allowance_amount` is one lump sum.**
A salary slip cannot be produced from a single allowance figure. It needs a
`salary_components` table (the heads a school defines) and
`staff_salary_structure` (what each person gets under each head), with earnings
and deductions distinguished.

**3. The teacher's classes are recorded; the rest of the teacher is not.**
`teacher_class_assignments` was built on 2026-09-08 to close attendance finding
A13 — it holds subject teacher and class teacher assignments per session, with
one class teacher per section enforced in the database, and `periods_per_week`
for workload. What is still missing is the teacher's own qualifications, their
subjects-they-may-teach list, exam duties, substitutions and training.

**4. The personal file is thin.**
No CNIC — so nothing stops the same person being entered twice. No emergency
contact, no qualifications, no documents, no licence expiry.

### Suggested order

**First — the foundation.** Multiple roles per person, and CNIC as the
identity. Nothing else is worth building until a person can hold two jobs, and
retrofitting it later means rewriting whatever was built on top.

**Second — the teacher.** Class assignments are done (A13 is closed and the
attendance module is finished). What is left here is the teacher's own record:
which subjects they may be given, exam duties, substitutions, training.

**Third — the personal file.** Qualifications, documents, emergency contact, and
the expiry warnings — the driving licence one above all.

**Fourth — attendance and leave.** Staff attendance with check-in times, leave
types with balances, and the connection between an approved leave and the
register.

**Fifth — payroll.** Salary heads, structures, advances, the monthly run, and
the lock. Last because it depends on all four above: it cannot be right until
attendance and leave feed it.

### Rules this module must follow

The same ones the fee and attendance modules were held to, and for the same
reasons — each of these was a real bug we have already fixed once:

- **History is never overwritten.** A salary revision opens a new period; it
  does not edit the old figure.
- **An approved payroll is locked**, the way an attendance register is, and the
  lock must be enforced on the path the screen actually uses — not only on the
  edit screen.
- **A person is identified by CNIC**, uniquely, at the database level. Not by a
  name, which repeats, and not only in the form, which can be bypassed.
- **Fixed lists the school extends** — designations, salary heads, leave types —
  stay plain string columns with their behaviour read from the row. Fixed lists
  the *system* reasons about are enums. Never cast a column the school can add
  to.
- **Every money figure is `decimal`**, never a float, and every total is
  recomputed from its parts rather than adjusted in place.

---

## Part 12 — One question for you

Everything above I can decide and build. This one is yours, because it is a rule
about how your school runs, not about software:

**When somebody does two jobs, how is their salary set?**

1. One salary for the person, agreed as a whole — the second job is part of what
   they are paid for. *(Simplest, and how most schools here actually do it.)*
2. A salary for the main job, plus a fixed extra allowance for the second.
3. A separate salary for each job, added together.

I would build **the first**, with the second available as an allowance head for
schools that want it — because that matches what schools here do, and the third
creates two salaries for one person, which is the very confusion Part 1 exists to
prevent.

Tell me which, and I will start with the foundation in Part 11.
