-- Update terminology for external programs
-- Change "participants" to "external entries" for clarity

USE entryx;

-- Add comment to max_participants column to clarify it's for external entries only
ALTER TABLE external_programs 
MODIFY COLUMN max_participants INT DEFAULT 500 
COMMENT 'Maximum number of external entries allowed for this program';

-- Success message
SELECT 'Terminology updated: max_participants now refers to external entries' AS MESSAGE;
