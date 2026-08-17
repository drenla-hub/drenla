# PM Notes — Staff Roles And Task Assignment

Date: 2026-07-02

## Review

CO started a staff/roles scaffold in the Laravel admin area without first claiming the work in `../collaboration-notes.md`. That crossed the build ownership boundary because `app/Http/Controllers/Admin/**`, `resources/views/admin/**`, and `routes/web.php` are CL-owned.

The work itself is directionally good and should not be reverted:
- `Admin\UserController` starts a Super Admin-only staff management flow.
- `routes/web.php` has the `admin.users.*` resource wired.
- `resources/views/admin/users/index.blade.php` starts the staff list and role boundary UI.

The scaffold is incomplete:
- `resources/views/admin/users/form.blade.php` is still missing.
- The staff route/controller needs feature tests.
- Task assignment still needs role-aware workload/progress context in the project task UI.
- CO/CL must not be used as product roles. They are collaboration agent labels, not real Drenla staff roles.
- The role set must be corrected back to real system roles before this feature is finished; specifically, do not ship `commercial_owner` or `client_lead` as app roles just because the build board uses CO/CL labels.

## Assignment

CL owns continuation from the scaffold because this is admin frontend and admin route work. CL should continue from the existing files rather than starting over.

CL next steps:
1. Finish `resources/views/admin/users/form.blade.php` for create/edit staff users.
2. Keep staff/role management limited to Super Admin.
3. Correct the role vocabulary to real Drenla system roles, not CO/CL agent labels.
4. Add task assignment context in `resources/views/admin/projects/show.blade.php`: show each staff user's real role label and current task progress so tasks can be assigned based on workload.
5. Add feature tests for staff list/create/update/delete permissions.
6. Update `../collaboration-notes.md` before touching these files.

CO next steps:
1. Do not continue coding in CL-owned admin files unless CL explicitly asks.
2. Review whether the role names and boundaries are correct from a product/business perspective.
3. If schema or backend policy changes are needed, log them in `../collaboration-notes.md` first.

## Product Direction

Do not model staff roles after the CL/CO build-agent split. Those labels are only for coordinating Claude/Codex work in `../tasks.md` and `../collaboration-notes.md`.

Use roles that match the actual system surfaces:
- Super Admin: full system control, staff management, and role changes.
- Admin/Manager: day-to-day operations across clients, projects, proposals, finance, inquiries, and settings.
- Finance: finance documents, invoices/receipts, payment status, and finance-linked project gating.
- Project Manager: projects, milestones, tasks, delivery status, and client-visible progress.
- Content Editor: homepage, case studies, articles, focus areas, media, and public content.
- Support/Sales: inquiries, client intake, client records, and proposal follow-up.

If the app should stay simpler for now, use only the current existing roles (`super_admin`, `manager`, `editor`) and make their boundaries explicit in the UI instead of adding fake role names.

The goal is not just "users CRUD". The goal is a clear operational system where staff roles are visible, task ownership is deliberate, and admins can assign work based on current progress instead of blindly selecting a name.
