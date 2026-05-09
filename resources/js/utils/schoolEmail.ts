export const buildSchoolEmailDomain = (schoolName?: string | null): string => {
    const words = (schoolName ?? '')
        .trim()
        .split(/[^A-Za-z0-9]+/)
        .filter(Boolean);

    const initials = words.map((word) => word[0].toLowerCase()).join('');

    return initials ? `${initials}school.com` : 'school.com';
};

export const buildGeneratedEmail = (
    personName?: string | null,
    schoolName?: string | null,
): string => {
    const localPart = (personName ?? '').toLowerCase().replace(/[^a-z0-9]+/g, '');

    return `${localPart || 'student'}@${buildSchoolEmailDomain(schoolName)}`;
};
